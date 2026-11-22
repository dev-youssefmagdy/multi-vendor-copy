<?php

namespace App\Repositories\Tenant\Catalog;

use App\Models\Tenant\Product;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogRef;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CentralTable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Tenant-side writes on the merged badge catalog (dedup plan phase 2, `catalog_source.badges = central` only).
 * Never writes central (invariant 1). What the vendor owns about a badge assignment lands in the tenant DB:
 *
 *  - central pair (central badge × central product the tenant carries): `tenant_badge_product_overrides`
 *    (sort_order override, `hidden` = removed by the vendor);
 *  - tenant attach to a central badge: tenant pivot row with `product_badge_id` NULL + `badge_type = central`,
 *    `catalog_badge_id` = central id (typed refs, §3.1);
 *  - own badge: tenant pivot row as today (+ typed pair).
 *
 * Same semantics as the sync-mode code it replaces (BadgeController::save, BadgeSortController::update,
 * `$product->badges()->sync()`), incl. "existing products keep their sort order, new ones are appended".
 */
final class BadgeAssignments
{
    public function __construct(private readonly BadgeCatalog $catalog)
    {
    }

    private function db(): ConnectionInterface
    {
        return DB::connection('tenant');
    }

    /**
     * Replace the product list of one badge + country (NULL = default rows) with $productIds (tenant product ids,
     * in the given order; caller filters them like the sync-mode save).
     *
     * @param  list<int>  $productIds
     */
    public function saveForBadge(CatalogBadge $badge, ?int $countryId, array $productIds): void
    {
        $this->db()->transaction(function () use ($badge, $countryId, $productIds) {
            $existingOrder = $this->catalog->entries($badge, $countryId)->pluck('m.sort_order', 'm.product_id')
                ->map(fn ($sort) => (int) $sort);
            $nextOrder = $existingOrder->isEmpty() ? 0 : $existingOrder->max() + 1;
            $centralPairs = $this->centralPairs($badge, $countryId); // tenant product id => central product id (incl. hidden)
            $wanted = array_flip($productIds);
            $products = $this->productRefs($productIds);
            $now = now();

            foreach ($productIds as $productId) {
                if (isset($centralPairs[$productId])) {
                    if (! $existingOrder->has($productId)) { // hidden central pair: show it again, appended
                        $this->upsertOverride($badge, $centralPairs[$productId], $countryId, ['hidden' => false, 'sort_order' => $nextOrder++]);
                    }

                    continue;
                }

                if ($existingOrder->has($productId) || ! isset($products[$productId])) {
                    continue;
                }

                $this->db()->table('product_badge_product')->insert($this->tenantRow($badge, $products[$productId], $countryId, $nextOrder++, $now));
            }

            foreach ($centralPairs as $productId => $centralProductId) {
                if (! isset($wanted[$productId])) {
                    $this->upsertOverride($badge, $centralProductId, $countryId, ['hidden' => true]);
                }
            }

            $this->tenantRows($badge)
                ->where(fn (Builder $q) => $countryId === null ? $q->whereNull('country_id') : $q->where('country_id', $countryId))
                ->when($productIds !== [], fn (Builder $q) => $q->whereNotIn('product_id', $productIds))
                ->delete();
        });

        $this->bump();
    }

    /**
     * Persist a drag-and-drop order (index = sort_order) for one badge + country.
     *
     * @param  list<int|string>  $productIds
     */
    public function saveSort(CatalogBadge $badge, ?int $countryId, array $productIds): void
    {
        $this->db()->transaction(function () use ($badge, $countryId, $productIds) {
            $centralPairs = $this->centralPairs($badge, $countryId);

            foreach (array_values($productIds) as $index => $productId) {
                $productId = (int) $productId;

                if (isset($centralPairs[$productId])) {
                    $this->upsertOverride($badge, $centralPairs[$productId], $countryId, ['sort_order' => $index], keepHidden: true);

                    continue;
                }

                $this->tenantRows($badge)
                    ->where('product_id', $productId)
                    ->where(fn (Builder $q) => $countryId === null ? $q->whereNull('country_id') : $q->where('country_id', $countryId))
                    ->update(['sort_order' => $index, 'updated_at' => now()]);
            }
        });

        $this->bump();
    }

    /**
     * Product form: the product carries exactly the badges in $refs (like BelongsToMany::sync() on the synced pivot:
     * unselected badges are removed in every country, newly selected ones are added as a default-country row).
     *
     * @param  iterable<mixed>  $refs  badge ref strings ("c:12" / "t:3"); unknown refs are ignored
     */
    public function syncProduct(Product $product, iterable $refs): void
    {
        $selected = $this->catalog->findMany($refs);
        $productId = (int) $product->getKey();
        $productRef = $this->productRefs([$productId])[$productId] ?? null;
        if ($productRef === null) {
            return;
        }

        $this->db()->transaction(function () use ($selected, $productId, $productRef) {
            $current = $this->catalog->membershipQuery('m')->where('m.product_id', $productId)
                ->get(['m.id', 'm.badge_type', 'm.badge_id', 'm.country_id', 'm.pair_source']);
            $now = now();

            // Remove unselected badges (every country).
            foreach ($current->whereNotIn('id', array_keys($selected)) as $row) {
                $badge = new CatalogBadge(CatalogRef::make($row->badge_type, $row->badge_id), '', true);
                if ($row->pair_source === 'central') {
                    $this->upsertOverride($badge, $productRef['catalog_product_id'], $row->country_id === null ? null : (int) $row->country_id, ['hidden' => true]);
                } else {
                    $this->tenantRows($badge)->where('product_id', $productId)
                        ->where(fn (Builder $q) => $row->country_id === null ? $q->whereNull('country_id') : $q->where('country_id', $row->country_id))
                        ->delete();
                }
            }

            // Add newly selected badges (default country), appended to that badge's default list.
            $currentIds = $current->pluck('id')->unique()->all();
            foreach ($selected as $ref => $badge) {
                if (in_array($ref, $currentIds, true)) {
                    continue;
                }

                $max = $this->catalog->entries($badge, null)->max('m.sort_order');
                $next = $max === null ? 0 : (int) $max + 1;

                if (isset($this->centralPairs($badge, null)[$productId])) {
                    $this->upsertOverride($badge, $productRef['catalog_product_id'], null, ['hidden' => false, 'sort_order' => $next]);
                } else {
                    $this->db()->table('product_badge_product')->insert($this->tenantRow($badge, $productRef, null, $next, $now));
                }
            }
        });

        $this->bump();
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    /**
     * Central pivot pairs of a central badge for one country, for products the tenant carries, INCLUDING pairs the
     * vendor hid: tenant product id => central product id. Empty for own badges.
     *
     * @return array<int, int>
     */
    private function centralPairs(CatalogBadge $badge, ?int $countryId): array
    {
        if (! $badge->isCentral()) {
            return [];
        }

        $notOwn = CatalogRefColumns::has($this->db(), 'products', 'is_own_product') ? 'COALESCE(p.`is_own_product`, 0) = 0' : '1 = 1';
        $rows = $this->db()->select(
            'SELECT p.`id` AS tenant_id, cp.`product_id` AS central_id
               FROM ' . CentralTable::name('product_badge_product') . " cp
               JOIN `products` p ON p.`central_product_id` = cp.`product_id` AND {$notOwn}
              WHERE cp.`product_badge_id` = ? AND cp.`country_id` <=> ?",
            [$badge->badgeId, $countryId],
        );

        $pairs = [];
        foreach ($rows as $row) {
            $pairs[(int) $row->tenant_id] ??= (int) $row->central_id;
        }

        return $pairs;
    }

    /** Tenant pivot rows that belong to $badge (central attach rows, or rows of an own badge). */
    private function tenantRows(CatalogBadge $badge): Builder
    {
        $query = $this->db()->table('product_badge_product');

        return $badge->isCentral()
            ? $query->whereNull('product_badge_id')->where('badge_type', CatalogRef::CENTRAL)->where('catalog_badge_id', $badge->badgeId)
            : $query->where('product_badge_id', $badge->badgeId);
    }

    /** @param  array{product_type: string, catalog_product_id: int}  $product */
    private function tenantRow(CatalogBadge $badge, array $product, ?int $countryId, int $sortOrder, \DateTimeInterface $now): array
    {
        return [
            'product_badge_id' => $badge->isCentral() ? null : $badge->badgeId,
            'badge_type' => $badge->ref->source,
            'catalog_badge_id' => $badge->badgeId,
            'product_id' => $product['id'],
            'product_type' => $product['product_type'],
            'catalog_product_id' => $product['catalog_product_id'],
            'country_id' => $countryId,
            'sort_order' => $sortOrder,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /** @param  array<string, mixed>  $values */
    private function upsertOverride(CatalogBadge $badge, int $centralProductId, ?int $countryId, array $values, bool $keepHidden = false): void
    {
        $now = now();
        $row = [
            'badge_type' => $badge->ref->source,
            'badge_id' => $badge->badgeId,
            'product_type' => CatalogRef::CENTRAL,
            'product_id' => $centralProductId,
            'country_id' => $countryId,
            'sort_order' => $values['sort_order'] ?? null,
            'hidden' => $values['hidden'] ?? false,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // ON DUPLICATE KEY on tbpo_pair_unique (country_key = IFNULL(country_id, 0)); only the given columns change.
        $update = array_keys(array_intersect_key($values, ['sort_order' => 1, 'hidden' => 1]));
        if ($keepHidden) {
            $update = array_values(array_diff($update, ['hidden']));
        }

        $this->db()->table(BadgeCatalog::OVERRIDES)->upsert(
            [$row],
            ['badge_type', 'badge_id', 'product_type', 'product_id', 'country_id'],
            [...$update, 'updated_at'],
        );
    }

    /**
     * Typed product refs of tenant products: id => [id, product_type, catalog_product_id] (CatalogRefResolver rule).
     *
     * @param  list<int>  $ids
     * @return array<int, array{id: int, product_type: string, catalog_product_id: int}>
     */
    private function productRefs(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $hasOwn = CatalogRefColumns::has($this->db(), 'products', 'is_own_product');
        $refs = [];
        foreach ($this->db()->table('products')->whereIn('id', $ids)->get(['id', 'central_product_id', ...($hasOwn ? ['is_own_product'] : [])]) as $row) {
            $central = $row->central_product_id !== null && ! ($hasOwn && $row->is_own_product);
            $refs[(int) $row->id] = [
                'id' => (int) $row->id,
                'product_type' => $central ? CatalogRef::CENTRAL : CatalogRef::TENANT,
                'catalog_product_id' => (int) ($central ? $row->central_product_id : $row->id),
            ];
        }

        return $refs;
    }

    private function bump(): void
    {
        CacheVersion::bumpMany(['ProductBadge', 'Product']);
    }
}
