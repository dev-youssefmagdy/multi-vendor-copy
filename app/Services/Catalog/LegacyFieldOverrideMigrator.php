<?php

namespace App\Services\Catalog;

use App\Enums\ProductEditRequestStatus;
use App\Models\ProductEditRequest;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\Catalog\FieldOverrideStore;
use App\Support\Catalog\EditableCentralFields;
use Illuminate\Support\Carbon;

/**
 * Dedup plan §4.2a data migration, per tenant product: a legacy `has_custom_translations = 1` product froze ALL its
 * translated fields; turn that into override rows for ONLY the whitelisted (field, locale) pairs whose tenant value
 * differs from central's current value. Locales central does not have (tenant-only, AI-translated languages,
 * class (d)) are not overrides and are left alone. Each differing pair is classified:
 *
 *  - `approved_request`: the product's approved edit request covers the pair and the tenant value = requested value;
 *  - `stale`: the approved request covers the pair and the tenant value = the request's PRE-approval value
 *    (`current_translations`), i.e. the approval is no longer in the tenant row: nothing proves it is a vendor value;
 *  - `tenant_diff`: everything else (older manual / AI edits, and approvals lost because `product_edit_requests`
 *    keeps only ONE row per tenant+product, so an earlier approved request A is overwritten by request B: QA D-P2-2).
 *
 * Stale pairs are never dropped silently ($stale): STALE_BLOCK (command default) writes nothing for such a product so
 * its legacy freeze stays, and reports it; STALE_DROP (`--accept-stale`, explicit acknowledgement) writes the other
 * pairs and lets the stale ones follow central; STALE_KEEP (lazy admin approve / reset) keeps them as `tenant_diff`.
 *
 * Additive: never replaces an existing override row, never changes translations or the product row. Used by
 * `catalog:dedup-migrate field_overrides` and lazily by the admin approve / reset of a not-yet-migrated product
 * (so an older approval is not lost when the product switches to pair-level overrides). Current tenant connection.
 */
final class LegacyFieldOverrideMigrator
{
    public const STALE_BLOCK = 'block';
    public const STALE_DROP = 'drop';
    public const STALE_KEEP = 'keep';

    public function __construct(private readonly FieldOverrideStore $store)
    {
    }

    /**
     * @param  iterable<object>  $products  tenant product rows: id, central_product_id, has_custom_translations, updated_at
     * @param  array<int, ProductEditRequest>  $approvedRequests  tenant product id => its approved edit request
     * @param  string  $stale  STALE_BLOCK | STALE_DROP | STALE_KEEP
     * @return array{products: int, rows: int, stale: int, blocked_products: int, stale_dropped: int, pairs: list<array<string, mixed>>, stale_pairs: list<array<string, mixed>>}
     */
    public function migrate(iterable $products, array $approvedRequests, bool $dryRun = false, string $stale = self::STALE_KEEP): array
    {
        if (! in_array($stale, [self::STALE_BLOCK, self::STALE_DROP, self::STALE_KEEP], true)) {
            throw new \InvalidArgumentException("Unknown stale mode [{$stale}].");
        }

        $type = EditableCentralFields::CENTRAL_PRODUCT;
        $fields = EditableCentralFields::for($type);
        $products = collect($products)->filter(fn ($p) => $p->central_product_id !== null && (bool) $p->has_custom_translations)->values();
        $result = ['products' => $products->count(), 'rows' => 0, 'stale' => 0, 'blocked_products' => 0, 'stale_dropped' => 0, 'pairs' => [], 'stale_pairs' => []];
        if ($products->isEmpty()) {
            return $result;
        }

        $central = $this->store->centralProductValues($products->pluck('central_product_id')->map(fn ($id) => (int) $id)->unique()->values()->all(), $fields);
        $tenant = $this->store->tenantValues((new Product())->getMorphClass(), $products->pluck('id')->map(fn ($id) => (int) $id)->all(), $fields);

        foreach ($products as $product) {
            $centralId = (int) $product->central_product_id;
            $request = $approvedRequests[(int) $product->id] ?? null;
            $existing = $this->store->valuesFor($type, [$centralId])[$centralId] ?? [];
            $write = [];
            $stalePairs = [];

            foreach ($central[$centralId] ?? [] as $locale => $centralFields) {
                foreach ($fields as $field) {
                    $value = $tenant[(int) $product->id][$locale][$field] ?? '';
                    $centralValue = $centralFields[$field] ?? '';
                    if ($value === '' || $value === $centralValue || isset($existing[$locale][$field])) {
                        continue;
                    }

                    $source = self::classify($request, (string) $locale, $field, $value);
                    $pair = [
                        'product_id' => (int) $product->id,
                        'central_product_id' => $centralId,
                        'field' => $field,
                        'locale' => $locale,
                        'source' => $source,
                        'edit_request_id' => $source === 'approved_request' ? $request->id : null,
                        'value' => $value,
                        'central_value' => $centralValue,
                    ];

                    if ($source === 'stale') {
                        $stalePairs[] = $pair;
                        if ($stale !== self::STALE_KEEP) {
                            continue;
                        }
                        $pair['source'] = 'tenant_diff';
                    }
                    $write[] = $pair;
                }
            }

            $result['stale'] += count($stalePairs);
            array_push($result['stale_pairs'], ...$stalePairs);
            if ($stalePairs !== [] && $stale === self::STALE_BLOCK) {
                // Not acknowledged: write nothing for this product, so the legacy freeze keeps ALL its values.
                $result['blocked_products']++;

                continue;
            }
            if ($stale === self::STALE_DROP) {
                $result['stale_dropped'] += count($stalePairs);
            }

            foreach ($write as $pair) {
                $fromRequest = $pair['source'] === 'approved_request';
                if (! $dryRun) {
                    $this->store->put(
                        $type, $centralId, $pair['field'], $pair['locale'], $pair['value'], $pair['central_value'],
                        $fromRequest ? (int) $request->id : null,
                        $fromRequest && $request->reviewed_at ? $request->reviewed_at : Carbon::parse($product->updated_at ?? now()),
                        $fromRequest ? ($request->reviewed_by !== null ? (int) $request->reviewed_by : null) : null,
                        onlyIfMissing: true,
                    );
                }

                unset($pair['value'], $pair['central_value']);
                $result['rows']++;
                $result['pairs'][] = $pair;
            }
        }

        $result['stale_pairs'] = array_map(function (array $p) {
            unset($p['value'], $p['central_value']);

            return $p;
        }, $result['stale_pairs']);

        return $result;
    }

    /**
     * `approved_request` | `stale` | `tenant_diff` for a tenant value that differs from central (see class doc).
     */
    public static function classify(?ProductEditRequest $request, string $locale, string $field, string $value): string
    {
        if ($request === null) {
            return 'tenant_diff';
        }

        $requested = data_get($request->requested_translations, "{$locale}.{$field}");
        if ($requested !== null && trim((string) $requested) === $value) {
            return 'approved_request';
        }

        $before = data_get($request->current_translations, "{$locale}.{$field}");
        if ($requested !== null && $before !== null && trim((string) $before) === $value) {
            return 'stale';
        }

        return 'tenant_diff';
    }

    /** Approved edit requests of a tenant, keyed by tenant product id (one request per product: unique key). */
    public static function approvedRequests(string $tenantId): array
    {
        return ProductEditRequest::query()
            ->where('tenant_id', $tenantId)
            ->where('status', ProductEditRequestStatus::Approved)
            ->get()
            ->keyBy(fn (ProductEditRequest $r) => (int) $r->product_id)
            ->all();
    }
}
