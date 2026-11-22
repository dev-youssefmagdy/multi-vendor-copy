<?php

namespace App\Services\Catalog\Dedup;

use App\Models\CatalogDedupRun;
use App\Models\Tenant;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\Catalog\FieldOverrideStore;
use App\Services\Catalog\LegacyFieldOverrideMigrator;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\EditableCentralFields;
use Illuminate\Support\Facades\DB;

/**
 * `catalog:dedup-migrate field_overrides` (dedup plan §4.2a / §5a.1 translations (b)/(c)): every legacy
 * `has_custom_translations = 1` central-linked product gets `tenant_field_overrides` rows for ONLY the whitelisted
 * (field, locale) pairs whose tenant value differs from central's current value (LegacyFieldOverrideMigrator).
 * A completed (non dry-run) migrate run is what lifts the legacy "freeze all 6 fields" in the products sync for this
 * tenant (TenantCatalogSyncService::legacyCustomTranslationsFreeze()).
 *
 * Stale pairs (QA D-P2-2: the approved request covers the pair but the tenant value is back to the request's
 * pre-approval value) are never dropped silently: without `--accept-stale` the migrate run writes nothing for such a
 * product and is recorded `failed` (`blocked`), so the legacy freeze stays and nothing is reverted; with it, they are
 * counted in `stale_dropped` and follow central. Every other differing pair (incl. an older approval lost because the
 * request row was overwritten by a later request) becomes a `tenant_diff` override.
 *
 * verify (read-only): for every custom / overridden product, each whitelisted pair in a central locale the tenant
 * has must read `override ?? central` = the tenant's current translation. Diffs = vendor values the migration did
 * not capture and the sync would overwrite, INCLUDING stale pairs (`stale_pairs`) unless a done migrate run of this
 * tenant acknowledged them (`--accept-stale`, then counted in `stale_accepted`). Pairs where the tenant value is
 * empty and central has one are reported as `will_fill_from_central` (intended: they follow central), and frozen
 * non-whitelisted fields that differ from central as `will_follow_central`.
 */
final class FieldOverridesDedup implements DedupSection
{
    private const EXAMPLES = 200;

    private const NOT_WHITELISTED = ['label', 'summary', 'meta_keywords', 'meta_description'];

    public function __construct(private readonly LegacyFieldOverrideMigrator $migrator)
    {
    }

    public function name(): string
    {
        return 'field_overrides';
    }

    public function notReadyReason(): ?string
    {
        $store = new FieldOverrideStore();

        return $store->ready() && CatalogRefColumns::has($store->db(), 'products', 'has_custom_translations')
            ? null
            : 'tenant_field_overrides missing (run tenants:migrate: 2026_11_24_000002)';
    }

    public function migrate(Tenant $tenant, bool $dryRun, array $options = []): array
    {
        $accept = (bool) ($options['accept_stale'] ?? false);
        $products = $this->customProducts();
        $result = DB::connection('tenant')->transaction(
            fn () => $this->migrator->migrate(
                $products,
                LegacyFieldOverrideMigrator::approvedRequests((string) $tenant->getTenantKey()),
                $dryRun,
                $accept ? LegacyFieldOverrideMigrator::STALE_DROP : LegacyFieldOverrideMigrator::STALE_BLOCK,
            )
        );

        $bySource = collect($result['pairs'])->countBy('source')->all();

        $report = [
            'counts' => [
                'custom_products' => $result['products'],
                'override_rows' => $result['rows'],
                'from_approved_request' => (int) ($bySource['approved_request'] ?? 0),
                'from_tenant_diff' => (int) ($bySource['tenant_diff'] ?? 0),
                'stale_pairs' => $result['stale'],
                'stale_blocked_products' => $result['blocked_products'],
                'stale_accepted' => $accept ? $result['stale_dropped'] : 0,
                'accept_stale' => $accept ? 1 : 0,
            ],
            'pairs' => array_slice($result['pairs'], 0, self::EXAMPLES),
            'stale_pairs' => array_slice($result['stale_pairs'], 0, self::EXAMPLES),
        ];

        if (! $dryRun && $result['blocked_products'] > 0) {
            $report['blocked'] = "{$result['stale']} stale pair(s) in {$result['blocked_products']} product(s) (see stale_pairs): "
                . 'those products keep their legacy freeze; review the report, then re-run with --accept-stale to let them follow central.';
        }

        return $report;
    }

    public function verify(Tenant $tenant): array
    {
        $store = new FieldOverrideStore();
        $type = EditableCentralFields::CENTRAL_PRODUCT;
        $fields = EditableCentralFields::for($type);
        $overridden = DB::connection('tenant')->table(FieldOverrideStore::TABLE)->where('entity_type', $type)->distinct()->pluck('entity_id')->map(fn ($id) => (int) $id)->all();

        $products = DB::connection('tenant')->table('products')
            ->whereNotNull('central_product_id')
            ->where(fn ($q) => $q->where('has_custom_translations', true)->orWhereIn('central_product_id', $overridden ?: [0]))
            ->get(['id', 'central_product_id']);

        $centralIds = $products->pluck('central_product_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $allFields = [...$fields, ...self::NOT_WHITELISTED];
        $central = $store->centralProductValues($centralIds, $allFields);
        $tenantValues = $store->tenantValues((new Product())->getMorphClass(), $products->pluck('id')->map(fn ($id) => (int) $id)->all(), $allFields);
        $overrides = $store->valuesFor($type, $centralIds);
        $tenantLocales = DB::connection('tenant')->table('languages')->pluck('code')->map(fn ($c) => (string) $c)->all();
        $approved = LegacyFieldOverrideMigrator::approvedRequests((string) $tenant->getTenantKey());
        $staleAccepted = $this->staleAccepted((string) $tenant->getTenantKey());

        $counts = ['products' => $products->count(), 'pairs_checked' => 0, 'diffs' => 0, 'will_fill_from_central' => 0,
            'will_follow_central' => 0, 'stale_pairs' => 0, 'stale_accepted' => 0];
        $diffs = [];

        foreach ($products as $product) {
            $centralId = (int) $product->central_product_id;
            foreach ($central[$centralId] ?? [] as $locale => $centralFields) {
                if (! in_array($locale, $tenantLocales, true)) {
                    continue;
                }
                $current = $tenantValues[(int) $product->id][$locale] ?? [];

                foreach ($fields as $field) {
                    $counts['pairs_checked']++;
                    $expected = $overrides[$centralId][$locale][$field] ?? ($centralFields[$field] ?? '');
                    $actual = $current[$field] ?? '';
                    if ($actual === trim($expected)) {
                        continue;
                    }
                    if ($actual === '') {
                        $counts['will_fill_from_central']++;

                        continue;
                    }
                    $stale = ! isset($overrides[$centralId][$locale][$field])
                        && LegacyFieldOverrideMigrator::classify($approved[(int) $product->id] ?? null, (string) $locale, $field, $actual) === 'stale';
                    if ($stale && $staleAccepted) {
                        $counts['stale_accepted']++; // acknowledged with --accept-stale: follows central (intended)

                        continue;
                    }
                    $counts['stale_pairs'] += $stale ? 1 : 0;
                    $counts['diffs']++;
                    if (count($diffs) < self::EXAMPLES) {
                        $diffs[] = ['product_id' => (int) $product->id, 'central_product_id' => $centralId, 'locale' => $locale, 'field' => $field, 'stale' => $stale];
                    }
                }

                foreach (self::NOT_WHITELISTED as $field) {
                    if (($current[$field] ?? '') !== ($centralFields[$field] ?? '')) {
                        $counts['will_follow_central']++;
                    }
                }
            }
        }

        return ['diffs' => $counts['diffs'], 'counts' => $counts, 'examples' => $diffs];
    }

    /** Whether a done (non dry-run) migrate run of this tenant was given --accept-stale. */
    private function staleAccepted(string $tenantId): bool
    {
        return CatalogDedupRun::query()
            ->where('tenant_id', $tenantId)
            ->where('section', 'field_overrides')
            ->where('step', 'migrate')
            ->where('status', CatalogDedupRun::DONE)
            ->get(['counts'])
            ->contains(fn (CatalogDedupRun $run) => (int) (((array) $run->counts)['accept_stale'] ?? 0) === 1);
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    private function customProducts()
    {
        return DB::connection('tenant')->table('products')
            ->whereNotNull('central_product_id')
            ->where('has_custom_translations', true)
            ->when(CatalogRefColumns::has(DB::connection('tenant'), 'products', 'is_own_product'), fn ($q) => $q->where(fn ($q) => $q->where('is_own_product', false)->orWhereNull('is_own_product')))
            ->get(['id', 'central_product_id', 'has_custom_translations', 'updated_at']);
    }
}
