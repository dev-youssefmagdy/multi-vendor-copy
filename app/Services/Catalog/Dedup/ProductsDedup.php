<?php

namespace App\Services\Catalog\Dedup;

use App\Models\Tenant;
use App\Repositories\Tenant\Catalog\ProductCatalog;
use App\Support\Catalog\CentralTable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * `catalog:dedup-migrate products` (dedup plan phase 5). Additive + idempotent: for every synced copy whose central
 * product is live and published it writes ONE `tenant_product_overrides` row holding the tenant-owned state (active,
 * featured, order_number, profit JSON incl. a pinned default price) and one `tenant_product_variant_overrides` row per
 * copy variant (active, profit, margin). Copies are never deleted or changed (phase 7); central is never written.
 * Orphan copies (central gone / unpublished) are reported, not adopted. Fail-closed: a missing schema = skipped, a
 * tenant that already has override rows differing from the copy is `blocked` unless identical (re-run is a no-op).
 *
 * verify (read-only): per carried central product the copy vs the merged read: active, featured, order_number,
 * default price (|diff| <= 0.01, through the sort_price cache); products only on one side. diffs must be 0 before the
 * `products` flag may flip.
 */
final class ProductsDedup implements DedupSection
{
    private const EXAMPLES = 200;

    public function name(): string
    {
        return 'products';
    }

    private function db(): ConnectionInterface
    {
        return DB::connection('tenant');
    }

    public function notReadyReason(): ?string
    {
        return ProductCatalog::schemaReady($this->db())
            ? null
            : 'tenant DB lacks the phase-5 override tables: run `php artisan tenants:migrate` first';
    }

    /** @return list<object> copies whose central product is live + published */
    private function carried(): array
    {
        $cp = CentralTable::name('products');
        $live = ProductCatalog::centralVisible('c', $this->db());

        return $this->db()->select(
            "SELECT p.`id`, p.`central_product_id`, p.`active`, p.`featured`, p.`order_number`, p.`profit`, p.`default_price`
               FROM `products` p JOIN {$cp} c ON c.`id` = p.`central_product_id` WHERE {$live}"
        );
    }

    /**
     * `--remap-carts` (run AFTER the flip; `--reverse` before a rollback to `sync`): rewrites the `product_id` / `variant_id` of
     * every `mobile_carts.items` entry between tenant-copy ids (sync) and merged product keys (central) through the entry's
     * typed pair (ProductKey, phase 1 wrote the pairs). Entries without a pair are dropped from the cart only when they cannot
     * be resolved (reported). Idempotent. Session carts of the storefront are not persisted server-side and simply expire
     * (cartItems() skips ids that no longer resolve); favourites / ratings / orders are pair-based and need no remap.
     *
     * @return array<string, mixed>
     */
    private function remapCarts(Tenant $tenant, bool $dryRun, bool $reverse): array
    {
        $db = $this->db();
        if (! $reverse && ! ProductCatalog::enabled($tenant)) {
            return ['counts' => ['carts' => 0, 'items_remapped' => 0, 'items_unmapped' => 0],
                'blocked' => 'flip catalog_source.products to central first (remap rewrites ids into the merged key space)'];
        }
        $counts = ['carts' => 0, 'items_remapped' => 0, 'items_unmapped' => 0];
        $copyByCentral = $reverse ? $db->table('products')->whereNotNull('central_product_id')->pluck('id', 'central_product_id') : collect();
        $variantByCentral = $reverse ? $db->table('product_variants')->whereNotNull('central_product_variant_id')->pluck('id', 'central_product_variant_id') : collect();

        foreach ($db->table('mobile_carts')->get(['id', 'items']) as $cart) {
            $items = json_decode((string) $cart->items, true);
            if (! is_array($items) || $items === []) {
                continue;
            }
            $counts['carts']++;
            $changed = false;
            foreach ($items as $i => $item) {
                if (! is_array($item)) {
                    continue;
                }
                $productKey = \App\Support\Catalog\ProductKey::fromPair($item['product_type'] ?? null, $item['catalog_product_id'] ?? null);
                if ($productKey === null) {
                    $counts['items_unmapped']++;
                    continue;
                }
                $variantKey = \App\Support\Catalog\ProductKey::fromPair($item['variant_type'] ?? null, $item['catalog_variant_id'] ?? null);
                if ($reverse) {
                    $ref = \App\Support\Catalog\ProductKey::ref($productKey);
                    $pid = $ref->isCentral() ? $copyByCentral->get($ref->id) : $ref->id;
                    if ($pid === null) {
                        $counts['items_unmapped']++;
                        continue;
                    }
                    $vref = $variantKey === null ? null : \App\Support\Catalog\ProductKey::ref($variantKey);
                    $vid = $vref === null ? null : ($vref->isCentral() ? $variantByCentral->get($vref->id) : $vref->id);
                    $new = ['product_id' => (int) $pid] + ($vid !== null ? ['variant_id' => (int) $vid] : []);
                } else {
                    $new = ['product_id' => $productKey] + ($variantKey !== null ? ['variant_id' => $variantKey] : []);
                }
                foreach ($new as $k => $v) {
                    if (($item[$k] ?? null) !== $v) {
                        $items[$i][$k] = $v;
                        $changed = true;
                        $counts['items_remapped']++;
                    }
                }
            }
            if ($changed && ! $dryRun) {
                $db->table('mobile_carts')->where('id', $cart->id)->update(['items' => json_encode($items)]);
            }
        }

        return ['counts' => $counts, 'note' => 'cart remap: ' . ($reverse ? 'merged keys -> tenant copy ids (rollback)' : 'tenant ids -> merged keys (after flip)')];
    }

    public function migrate(Tenant $tenant, bool $dryRun, array $options = []): array
    {
        if (! empty($options['remap_carts'])) {
            return $this->remapCarts($tenant, $dryRun, ! empty($options['reverse']));
        }

        $db = $this->db();
        $carried = $this->carried();
        $counts = ['copies' => (int) $db->scalar('SELECT COUNT(*) FROM `products` WHERE `central_product_id` IS NOT NULL'),
            'carried' => count($carried), 'own' => (int) $db->scalar('SELECT COUNT(*) FROM `products` WHERE `central_product_id` IS NULL'),
            'overrides_written' => 0, 'variant_overrides_written' => 0];
        $counts['orphans'] = $counts['copies'] - $counts['carried'];

        $existing = $db->table(ProductCatalog::OVERRIDES)->pluck('central_product_id')->flip();
        $now = now();
        $rows = [];
        foreach ($carried as $p) {
            if ($existing->has((int) $p->central_product_id)) {
                continue;
            }
            $rows[] = ['central_product_id' => (int) $p->central_product_id, 'active' => (bool) $p->active, 'featured' => (bool) $p->featured,
                'order_number' => $p->order_number, 'profit' => $p->profit, 'adopted_at' => $now, 'created_at' => $now, 'updated_at' => $now];
        }
        $counts['overrides_written'] = count($rows);

        $vrows = [];
        $cv = CentralTable::name('product_variants');
        $existingV = $db->table(ProductCatalog::VARIANT_OVERRIDES)->pluck('central_variant_id')->flip();
        $variants = $db->select(
            "SELECT v.`central_product_variant_id` AS vid, c.`product_id` AS pid, v.`active`, v.`profit`, v.`margin_percentage`
               FROM `product_variants` v JOIN {$cv} c ON c.`id` = v.`central_product_variant_id`
              WHERE v.`central_product_variant_id` IS NOT NULL"
        );
        $carriedIds = collect($carried)->pluck('central_product_id')->flip();
        foreach ($variants as $v) {
            if ($existingV->has((int) $v->vid) || ! $carriedIds->has((int) $v->pid)) {
                continue;
            }
            // margin_percentage stays NULL: it is a vendor-typed margin set only through the panel; the copied profit JSON is the price truth.
            $vrows[] = ['central_variant_id' => (int) $v->vid, 'central_product_id' => (int) $v->pid, 'active' => (bool) $v->active,
                'profit' => $v->profit, 'margin_percentage' => null, 'created_at' => $now, 'updated_at' => $now];
        }
        $counts['variant_overrides_written'] = count($vrows);

        if (! $dryRun) {
            $db->transaction(function () use ($db, $rows, $vrows) {
                foreach (array_chunk($rows, 500) as $chunk) {
                    $db->table(ProductCatalog::OVERRIDES)->insertOrIgnore($chunk);
                }
                foreach (array_chunk($vrows, 500) as $chunk) {
                    $db->table(ProductCatalog::VARIANT_OVERRIDES)->insertOrIgnore($chunk);
                }
            });
        }

        return ['counts' => $counts, 'note' => 'products: additive override rows only; copies stay until phase 7.'];
    }

    public function verify(Tenant $tenant): array
    {
        $db = $this->db();
        $merged = collect($db->select('SELECT * FROM (' . ProductCatalog::productsSql((float) ($tenant->profit_percentage ?? 0), $db) . ') m WHERE m.`source` = \'central\' AND m.`central_visible` = 1'))
            ->keyBy(fn ($r) => (int) $r->central_product_id);
        $copies = collect($this->carried())->keyBy(fn ($r) => (int) $r->central_product_id);

        $examples = [];
        $diffs = 0;
        $note = function (string $class, int $id) use (&$examples, &$diffs) {
            $diffs++;
            if (count($examples) < self::EXAMPLES) {
                $examples[] = ['class' => $class, 'central' => $id];
            }
        };
        foreach ($copies as $id => $c) {
            $m = $merged->get($id);
            if ($m === null) {
                $note('missing_in_merged', $id);
            } elseif ((bool) $c->active !== (bool) $m->active || (bool) $c->featured !== (bool) $m->featured
                || $c->order_number != $m->order_number) {
                $note('field_diff', $id);
            } elseif (abs((float) $c->default_price - (float) $m->sort_price) > 0.01) {
                $note('price_diff', $id);
            }
        }
        // Price parity (guards price correctness): the per-country price map the merged read would produce from the
        // OVERRIDE row (override profit, central base + shipping costs, the tenant's profit %, through
        // TenantPricingService::forCatalog, the same call the sync uses) must equal the copy's stored `price` JSON
        // key by key and its `default_price`. Variants: `sell_price` / `default_sell_price` likewise.
        $pricing = app(ProductCatalog::class);
        $pct = (float) ($tenant->profit_percentage ?? 0);
        $cp = CentralTable::name('products');
        $centralRows = collect($db->select("SELECT `id`, `sale_price`, `base_price`, `fixed_shipping_costs` FROM {$cp}"))->keyBy('id');
        $overrides = $db->table(ProductCatalog::OVERRIDES)->get()->keyBy('central_product_id');
        $svc = app(\App\Services\Tenant\TenantPricingService::class);
        $copyRows = $db->table('products')->whereNotNull('central_product_id')->get(['id', 'central_product_id', 'price', 'default_price', 'fixed_shipping_costs'])->keyBy('central_product_id');
        $priceDiffs = 0;
        foreach ($copies as $id => $c) {
            $central = $centralRows->get($id);
            $override = $overrides->get($id);
            $copy = $copyRows->get($id);
            if ($central === null || $override === null || $copy === null) {
                continue; // already counted as missing_in_merged
            }
            $costs = json_decode((string) $copy->fixed_shipping_costs, true);
            $profit = json_decode((string) ($override->profit ?? 'null'), true);
            $map = $pricing->priceMap($svc->centralBasePrice($central), is_array($costs) ? $costs : null, is_array($profit) ? $profit : null, $pct);
            $stored = (array) json_decode((string) $copy->price, true);
            $bad = abs((float) $copy->default_price - $map['default']) > 0.01;
            foreach ($stored as $key => $value) {
                if (isset($map['prices'][(string) $key]) && abs((float) $value - $map['prices'][(string) $key]) > 0.01) {
                    $bad = true;
                }
            }
            if ($bad) {
                $priceDiffs++;
                $note('price_map_diff', $id);
            }
        }

        foreach ($merged->keys() as $id) {
            if (! $copies->has($id)) {
                $note('only_in_merged', $id);
            }
        }

        return ['diffs' => $diffs, 'counts' => ['copies' => $copies->count(), 'merged' => $merged->count(), 'price_map_diffs' => $priceDiffs], 'examples' => $examples];
    }
}
