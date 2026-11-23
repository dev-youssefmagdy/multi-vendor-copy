<?php

namespace App\Services\Catalog;

use App\Enums\EmailTemplateType;
use App\Enums\PaymentGatewayType;
use App\Enums\ProductEditRequestStatus;
use App\Models\Tenant;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use App\Support\Catalog\CentralTable;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Read-only drift report between central and each tenant's synced catalog copies (dedup plan §2b.1, "before
 * cutover" part). Used by `catalog:sync-check`.
 *
 * Everything runs on the CENTRAL connection with schema-qualified names (`<central db>`.`t`, `<tenant db>`.`t`;
 * invariant 2: same server, same user), so tenancy is never initialised and, on MySQL, the session is switched to
 * READ ONLY for the whole run: the server rejects any write, whatever code path tried it.
 *
 * Metrics per tenant + section (null = not applicable / column missing):
 *  rows, linked (central link set), own (no link), missing (live central rows this tenant should carry but has no
 *  copy of; only where the sync copies everything for the tenant), extra (copy of a live central row the tenant
 *  should not carry), stale_missing (link to a central row that no longer exists), stale_trashed (link to a
 *  soft-deleted central row), duplicate_links (several tenant rows linked to one central row), plus
 *  section-specific ones (central_visible_mismatch, orphan_variants, stale_variants, variant_product_mismatch,
 *  custom_translations_without_request, duplicate_texts, translations.{equal,different,tenant_only_language,
 *  tenant_only_field}).
 */
class CatalogSyncCheck
{
    /**
     * Pseudo-section (dedup plan phase 1): typed reference pairs per table. Metrics: null_refs (source id set, pair
     * NULL = backfill pending), unmapped_refs (of those, the tenant row is gone: cannot be mapped), dangling_refs
     * (pair set but the referenced central/tenant row no longer exists), plus the same per table under `tables`.
     */
    public const REFS = 'refs';

    /** entity => central table a `central` ref points at (dangling check). */
    private const REF_CENTRAL_TABLES = [
        'product' => 'products',
        'variant' => 'product_variants',
        'category' => 'categories',
        'badge' => 'product_badges',
        'label' => 'product_labels',
        'flash_sale' => 'central_flash_sales',
    ];

    /**
     * section => [tenant table, link column, central table, central key, "expected" rule]. "expected" = 'all', a
     * method name returning [sql, bindings] for alias `c`, or null when the tenant only carries a subset that
     * cannot be derived here (adoption, country targeting, purchases).
     */
    private const SPECS = [
        'languages' => ['languages', 'central_language_id', 'languages', 'id', null],
        'currencies' => ['currencies', 'central_currency_id', 'currencies', 'id', 'all'],
        'settings' => null, // keyed by name, no central link: mode only
        'payment-gateways' => ['payment_gateways', 'central_payment_gateway_id', 'payment_gateways', 'id', 'expectedPaymentGateways'],
        'themes' => ['themes', 'central_theme_id', 'templates', 'id', 'all'],
        'email-templates' => ['email_templates', 'central_email_template_id', 'email_templates', 'id', 'expectedEmailTemplates'],
        'pages' => ['pages', 'slug', 'static_pages', 'slug', 'all'],
        'categories' => ['categories', 'central_category_id', 'categories', 'id', 'expectedCategories'],
        'products' => ['products', 'central_product_id', 'products', 'id', null],
        'badges' => ['product_badges', 'central_product_badge_id', 'product_badges', 'id', 'all'],
        'labels' => ['product_labels', 'central_product_label_id', 'product_labels', 'id', 'all'],
        'flash-sales' => ['flash_sales', 'central_flash_sale_id', 'central_flash_sales', 'id', null],
        'coupons' => ['coupons', 'central_coupon_id', 'central_coupons', 'id', null],
    ];

    /** Translation parity: section => [tenant morph type, central morph type]. */
    private const TRANSLATED = [
        'products' => [\App\Models\Tenant\Product::class, \App\Models\Product::class],
        'categories' => [\App\Models\Tenant\Category::class, \App\Models\Category::class],
        'pages' => [\App\Models\Tenant\Page::class, \App\Models\StaticPage::class],
    ];

    private Connection $db;

    /** @var array<string, array<string, true>> central table => columns */
    private array $centralColumns = [];

    /** @var array<string, array<string, true>> tenant table => columns (current tenant) */
    private array $tenantColumns = [];

    private string $tenantDb = '';

    private ?Tenant $tenant = null;

    public function __construct()
    {
        $this->db = DB::connection(config('tenancy.database.central_connection'));
    }

    /**
     * @param  string[]  $tenantIds  empty = every tenant
     * @param  string[]  $sections  empty = every section
     * @return list<array<string, mixed>>
     */
    public function run(array $tenantIds = [], array $sections = []): array
    {
        $all = [...CatalogSource::SECTIONS, self::REFS];
        $sections = $sections === [] ? $all : array_values(array_intersect($all, $sections));

        return $this->readOnly(function () use ($tenantIds, $sections) {
            $this->centralColumns = $this->columnsOf(CentralTable::database());

            $tenants = Tenant::query()
                ->when($tenantIds !== [], fn ($q) => $q->whereIn('id', $tenantIds))
                ->orderBy('id')
                ->get();

            $report = [];
            foreach ($tenants as $tenant) {
                $report[] = $this->checkTenant($tenant, $sections);
            }

            return $report;
        });
    }

    /** @return array<string, mixed> */
    private function checkTenant(Tenant $tenant, array $sections): array
    {
        $this->tenant = $tenant;
        $this->tenantDb = $tenant->database()->getName();

        $result = [
            'tenant' => (string) $tenant->getTenantKey(),
            'database' => $this->tenantDb,
            'sync_status' => $tenant->getAttribute('sync_status'),
            'catalog_source' => CatalogSource::map($tenant),
            'skipped' => null,
            'sections' => [],
        ];

        $exists = $this->db->selectOne('SELECT 1 AS ok FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$this->tenantDb]);
        if ($exists === null) {
            $result['skipped'] = 'tenant database does not exist';

            return $result;
        }

        $this->tenantColumns = $this->columnsOf($this->tenantDb);

        foreach ($sections as $section) {
            try {
                $metrics = $this->checkSection($section);
            } catch (Throwable $e) {
                $metrics = ['error' => $e->getMessage()];
            }

            $result['sections'][$section] = ['mode' => $section === self::REFS ? '-' : CatalogSource::for($tenant, $section)] + $metrics;
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function checkSection(string $section): array
    {
        if ($section === self::REFS) {
            return $this->refMetrics();
        }

        $spec = self::SPECS[$section];
        if ($spec === null) {
            // Phase 6: settings are keyed by name; the dedup run is the only measurable thing.
            return ['note' => 'no central link (keyed by name)'] + $this->dedupMetrics('settings');
        }

        [$table, $link, $centralTable, $centralKey, $expected] = $spec;

        if (! isset($this->tenantColumns[$table])) {
            return ['error' => "tenant table [{$table}] missing"];
        }
        if (! isset($this->centralColumns[$centralTable])) {
            return ['error' => "central table [{$centralTable}] missing"];
        }

        $metrics = ['rows' => (int) $this->scalar("SELECT COUNT(*) FROM {$this->t($table)}")];

        if (! $this->tenantHas($table, $link)) {
            return $metrics + ['note' => "tenant column [{$table}.{$link}] missing (migration not run)"];
        }

        $metrics += $this->linkMetrics($section, $table, $link, $centralTable, $centralKey, $expected, $metrics['rows']);

        return $metrics + match ($section) {
            'products' => $this->productMetrics(),
            'categories' => $this->visibilityMetric('categories', 'central_category_id', 'categories', "c.status = 'published'")
                + $this->categoryParityMetrics() + $this->dedupMetrics('categories'),
            'badges' => ['duplicate_texts' => (int) $this->scalar(
                "SELECT COUNT(*) FROM (SELECT text FROM {$this->t('product_badges')} GROUP BY text HAVING COUNT(*) > 1) d"
            )] + $this->badgeParityMetrics() + $this->dedupMetrics('badges'),
            'labels' => $this->labelParityMetrics() + $this->dedupMetrics('labels'),
            'coupons' => $this->couponParityMetrics() + $this->dedupMetrics('coupons'),
            'flash-sales' => $this->flashSaleParityMetrics() + $this->dedupMetrics('flash_sales'),
            // Dedup plan phase 6: config entities report the dedup migrate / verify state (fail-closed gate
            // `dedup_verify_diffs:0`, like every other section).
            'languages', 'currencies', 'themes', 'pages' => $this->dedupMetrics($section),
            'payment-gateways' => $this->dedupMetrics('payment_gateways'),
            'email-templates' => $this->dedupMetrics('email_templates'),
            default => [],
        } + (isset(self::TRANSLATED[$section]) ? ['translations' => $this->translationParity($section, $table, $link, $centralTable, $centralKey)] : []);
    }

    /** @return array<string, int|null> */
    private function linkMetrics(string $section, string $table, string $link, string $centralTable, string $centralKey, ?string $expected, int $rows): array
    {
        $linked = $this->linkedCondition($section, $table, $link);
        $live = $this->centralLive($centralTable);
        [$expectedSql, $expectedBindings] = $this->expected($expected);
        $c = CentralTable::name($centralTable);

        $row = $this->db->selectOne(
            "SELECT
                SUM(CASE WHEN {$linked} THEN 1 ELSE 0 END) AS linked,
                SUM(CASE WHEN {$linked} AND c.`{$centralKey}` IS NULL THEN 1 ELSE 0 END) AS stale_missing,
                SUM(CASE WHEN {$linked} AND c.`{$centralKey}` IS NOT NULL AND NOT ({$live}) THEN 1 ELSE 0 END) AS stale_trashed,
                SUM(CASE WHEN {$linked} AND c.`{$centralKey}` IS NOT NULL AND ({$live}) AND NOT ({$expectedSql}) THEN 1 ELSE 0 END) AS extra
            FROM {$this->t($table)} t
            LEFT JOIN {$c} c ON c.`{$centralKey}` = t.`{$link}`",
            $expectedBindings,
        );

        $missing = $expected === null ? null : (int) $this->scalar(
            "SELECT COUNT(*) FROM {$c} c
             WHERE ({$live}) AND ({$expectedSql})
               AND NOT EXISTS (SELECT 1 FROM {$this->t($table)} t WHERE t.`{$link}` = c.`{$centralKey}` AND {$linked})",
            $expectedBindings,
        );

        $duplicates = (int) $this->scalar(
            "SELECT COUNT(*) FROM (SELECT t.`{$link}` FROM {$this->t($table)} t WHERE {$linked} GROUP BY t.`{$link}` HAVING COUNT(*) > 1) d"
        );

        return [
            'linked' => (int) $row->linked,
            'own' => $rows - (int) $row->linked,
            'missing' => $missing,
            'extra' => $expected === null ? null : (int) $row->extra,
            'stale_missing' => (int) $row->stale_missing,
            'stale_trashed' => (int) $row->stale_trashed,
            'duplicate_links' => $duplicates,
        ];
    }

    /** SQL (alias t) for "this tenant row is a sync copy of a central row". */
    private function linkedCondition(string $section, string $table, string $link): string
    {
        if ($section === 'pages') {
            // Pages are linked by slug; `source` (Phase -1) tells sync-owned rows apart once the tenant is migrated.
            return $this->tenantHas('pages', 'source')
                ? "t.`source` = 'central'"
                : 'EXISTS (SELECT 1 FROM ' . CentralTable::name('static_pages') . ' sp WHERE sp.`slug` = t.`slug`)';
        }

        return "t.`{$link}` IS NOT NULL";
    }

    /** @return array{0: string, 1: array<int, mixed>} */
    private function expected(?string $rule): array
    {
        return match ($rule) {
            null, 'all' => ['1 = 1', []],
            default => $this->{$rule}(),
        };
    }

    /** @return array{0: string, 1: array<int, mixed>} */
    private function expectedPaymentGateways(): array
    {
        return ['c.`type` = ?', [PaymentGatewayType::Orders->value]];
    }

    /** @return array{0: string, 1: array<int, mixed>} */
    private function expectedEmailTemplates(): array
    {
        return ['c.`type` = ?', [EmailTemplateType::Tenant->value]];
    }

    /**
     * The tenant's chosen root categories + their live descendants (same rule as the sync); none chosen = all.
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function expectedCategories(): array
    {
        $roots = $this->tenant->category_ids;
        if ($roots === []) {
            return ['1 = 1', []];
        }

        $children = [];
        foreach ($this->db->select('SELECT id, parent_id FROM ' . CentralTable::name('categories') . ' c WHERE ' . $this->centralLive('categories')) as $row) {
            if ($row->parent_id !== null) {
                $children[(int) $row->parent_id][] = (int) $row->id;
            }
        }

        $ids = $roots;
        $queue = $roots;
        while ($queue !== []) {
            foreach ($children[array_shift($queue)] ?? [] as $child) {
                if (! in_array($child, $ids, true)) {
                    $ids[] = $child;
                    $queue[] = $child;
                }
            }
        }

        return ['c.`id` IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids];
    }

    /** @return array<string, int|null> */
    private function productMetrics(): array
    {
        $metrics = $this->visibilityMetric('products', 'central_product_id', 'products', "c.status = 'published'");

        $p = $this->t('products');
        $v = $this->t('product_variants');
        $cv = CentralTable::name('product_variants');

        if ($this->tenantHas('product_variants', 'central_product_variant_id')) {
            $row = $this->db->selectOne(
                "SELECT
                    COUNT(*) AS variants,
                    SUM(CASE WHEN p.central_product_id IS NOT NULL AND v.central_product_variant_id IS NULL THEN 1 ELSE 0 END) AS orphan_variants,
                    SUM(CASE WHEN v.central_product_variant_id IS NOT NULL AND (cv.id IS NULL OR NOT ({$this->centralLive('product_variants', 'cv')})) THEN 1 ELSE 0 END) AS stale_variants,
                    SUM(CASE WHEN cv.id IS NOT NULL AND p.central_product_id IS NOT NULL AND cv.product_id <> p.central_product_id THEN 1 ELSE 0 END) AS variant_product_mismatch
                FROM {$v} v
                LEFT JOIN {$p} p ON p.id = v.product_id
                LEFT JOIN {$cv} cv ON cv.id = v.central_product_variant_id"
            );

            $metrics += [
                'variants' => (int) $row->variants,
                'orphan_variants' => (int) $row->orphan_variants,
                'stale_variants' => (int) $row->stale_variants,
                'variant_product_mismatch' => (int) $row->variant_product_mismatch,
            ];
        }

        $metrics['custom_translations_without_request'] = $this->tenantHas('products', 'has_custom_translations')
            ? (int) $this->scalar(
                "SELECT COUNT(*) FROM {$p} p
                 WHERE p.has_custom_translations = 1 AND p.central_product_id IS NOT NULL
                   AND NOT EXISTS (
                       SELECT 1 FROM " . CentralTable::name('product_edit_requests') . ' r
                       WHERE r.tenant_id = ? AND r.product_id = p.id AND r.status = ?
                   )',
                [(string) $this->tenant->getTenantKey(), ProductEditRequestStatus::Approved->value],
            )
            : null;

        return $metrics + $this->productParityMetrics() + $this->fieldOverrideMetrics()
            + $this->dedupMetrics('field_overrides', 'field_overrides_') + $this->dedupMetrics('products');
    }

    /**
     * Dedup phase 5 parity (gates, e.g. --gate=carried_without_override:0 --gate=dedup_verify_diffs:0):
     *  carried_without_override  copies of live published central products with no tenant_product_overrides row
     *                            (would vanish in central mode: run catalog:dedup-migrate products)
     *  override_without_central   override rows whose central product is gone / unpublished (harmless, hidden)
     *
     * @return array<string, int|null>
     */
    private function productParityMetrics(): array
    {
        if (! $this->tenantHas('products', 'central_product_id') || ! isset($this->tenantColumns['tenant_product_overrides'])) {
            return ['carried_without_override' => null, 'override_without_central' => null];
        }
        $p = $this->t('products');
        $cp = CentralTable::name('products');
        $o = $this->t('tenant_product_overrides');

        // Gate for the cart remap (--gate=mobile_cart_items_without_ref:0): items without a typed pair cannot be remapped at the flip.
        $cartsWithoutRef = 0;
        if (isset($this->tenantColumns['mobile_carts'])) {
            foreach ($this->db->select("SELECT `items` FROM {$this->t('mobile_carts')}") as $cart) {
                foreach ((array) json_decode((string) $cart->items, true) as $item) {
                    if (is_array($item) && (! isset($item['product_type']) || ! isset($item['catalog_product_id']))) {
                        $cartsWithoutRef++;
                    }
                }
            }
        }

        return [
            'mobile_cart_items_without_ref' => $cartsWithoutRef,
            'carried_without_override' => (int) $this->scalar(
                "SELECT COUNT(*) FROM {$p} p JOIN {$cp} c ON c.id = p.central_product_id AND c.status = 'published' AND {$this->centralLive('products', 'c')}
                 WHERE NOT EXISTS (SELECT 1 FROM {$o} o WHERE o.central_product_id = p.central_product_id)"
            ),
            'override_without_central' => (int) $this->scalar(
                "SELECT COUNT(*) FROM {$o} o LEFT JOIN {$cp} c ON c.id = o.central_product_id
                 WHERE c.id IS NULL OR c.status <> 'published' OR NOT ({$this->centralLive('products', 'c')})"
            ),
        ];
    }

    /** SQL (alias $alias) for "tenant product is NOT an own product". */
    private function notOwn(string $alias): string
    {
        return $this->tenantHas('products', 'is_own_product') ? "COALESCE({$alias}.`is_own_product`, 0) = 0" : '1 = 1';
    }

    /**
     * Dedup phase 2 badge parity (usable as gates, e.g. --gate=pairs_missing_in_copy:0):
     *  pairs_missing_in_copy  central pivot pairs of carried products with no tenant copy row (sync lag; would show
     *                         up as new in central mode → run the badges sync before migrate/verify)
     *  sort_overrides_pending copy rows of a central pair whose sort differs from central and have no override row yet
     *  attaches_pending       copy rows on a central-linked badge that central does not have (vendor attaches) and no
     *                         typed attach row yet
     *  override_rows / central_attach_rows  phase-2 rows (null before the tenant migration)
     *
     * @return array<string, int|null>
     */
    private function badgeParityMetrics(): array
    {
        if (! $this->tenantHas('product_badges', 'central_product_badge_id') || ! isset($this->centralColumns['product_badge_product']['country_id'])) {
            return [];
        }

        $t = $this->t('product_badge_product');
        $b = $this->t('product_badges');
        $p = $this->t('products');
        $cp = CentralTable::name('product_badge_product');
        $hasOverrides = isset($this->tenantColumns['tenant_badge_product_overrides']);
        $hasAttach = $this->tenantHas('product_badge_product', 'catalog_badge_id');
        $o = $this->t('tenant_badge_product_overrides');
        $centralPair = "SELECT 1 FROM {$cp} cp WHERE cp.product_badge_id = b.central_product_badge_id AND cp.product_id = p.central_product_id AND cp.country_id <=> t.country_id";

        $missing = (int) $this->scalar(
            "SELECT COUNT(*) FROM {$cp} cp JOIN {$p} p ON p.central_product_id = cp.product_id AND {$this->notOwn('p')}
              WHERE NOT EXISTS (SELECT 1 FROM {$t} t JOIN {$b} b ON b.id = t.product_badge_id
                                 WHERE b.central_product_badge_id = cp.product_badge_id AND t.product_id = p.id AND t.country_id <=> cp.country_id)"
        );

        $overrideExists = $hasOverrides
            ? "EXISTS (SELECT 1 FROM {$o} o WHERE o.badge_type = 'central' AND o.badge_id = b.central_product_badge_id AND o.product_type = 'central' AND o.product_id = p.central_product_id AND o.country_key = IFNULL(t.country_id, 0))"
            : '0 = 1';
        $attachExists = $hasAttach
            ? "EXISTS (SELECT 1 FROM {$t} a WHERE a.product_badge_id IS NULL AND a.badge_type = 'central' AND a.catalog_badge_id = b.central_product_badge_id AND a.product_id = t.product_id AND a.country_id <=> t.country_id)"
            : '0 = 1';

        $row = $this->db->selectOne(
            "SELECT
                SUM(CASE WHEN p.central_product_id IS NOT NULL AND {$this->notOwn('p')} AND EXISTS ({$centralPair} AND cp.sort_order <> t.sort_order) AND NOT {$overrideExists} THEN 1 ELSE 0 END) AS sort_pending,
                SUM(CASE WHEN NOT (p.central_product_id IS NOT NULL AND {$this->notOwn('p')} AND EXISTS ({$centralPair})) AND NOT {$attachExists} THEN 1 ELSE 0 END) AS attach_pending
             FROM {$t} t
             JOIN {$b} b ON b.id = t.product_badge_id AND b.central_product_badge_id IS NOT NULL
             LEFT JOIN {$p} p ON p.id = t.product_id"
        );

        return [
            'pairs_missing_in_copy' => $missing,
            'sort_overrides_pending' => (int) $row->sort_pending,
            'attaches_pending' => (int) $row->attach_pending,
            'override_rows' => $hasOverrides ? (int) $this->scalar("SELECT COUNT(*) FROM {$o}") : null,
            'central_attach_rows' => $hasAttach ? (int) $this->scalar("SELECT COUNT(*) FROM {$t} WHERE product_badge_id IS NULL") : null,
        ];
    }

    /**
     * Dedup phase 2 label parity (labels = DROP, read central directly): pairs_missing_in_copy / pairs_extra_in_copy
     * (central product_label_product of carried products vs the tenant copy pivot) and stale_copies (copy fields ≠
     * central). All must be 0 for the labels verify to pass.
     *
     * @return array<string, int|null>
     */
    private function labelParityMetrics(): array
    {
        if (! $this->tenantHas('product_labels', 'central_product_label_id') || ! isset($this->tenantColumns['product_label_product'])) {
            return [];
        }

        $t = $this->t('product_label_product');
        $l = $this->t('product_labels');
        $p = $this->t('products');
        $cpl = CentralTable::name('product_label_product');
        $cl = CentralTable::name('product_labels');

        return [
            'pairs_missing_in_copy' => (int) $this->scalar(
                "SELECT COUNT(*) FROM {$cpl} cp JOIN {$p} p ON p.central_product_id = cp.product_id AND {$this->notOwn('p')}
                 JOIN {$cl} c ON c.id = cp.product_label_id AND {$this->centralLive('product_labels')}
                  WHERE NOT EXISTS (SELECT 1 FROM {$t} t JOIN {$l} l ON l.id = t.product_label_id
                                     WHERE l.central_product_label_id = cp.product_label_id AND t.product_id = p.id)"
            ),
            'pairs_extra_in_copy' => (int) $this->scalar(
                "SELECT COUNT(*) FROM {$t} t JOIN {$l} l ON l.id = t.product_label_id AND l.central_product_label_id IS NOT NULL
                 LEFT JOIN {$p} p ON p.id = t.product_id
                  WHERE NOT EXISTS (SELECT 1 FROM {$cpl} cp JOIN {$cl} c ON c.id = cp.product_label_id AND {$this->centralLive('product_labels')}
                                     WHERE cp.product_label_id = l.central_product_label_id AND cp.product_id = p.central_product_id)"
            ),
            'stale_copies' => (int) $this->scalar(
                "SELECT COUNT(*) FROM {$l} l JOIN {$cl} c ON c.id = l.central_product_label_id
                  WHERE NOT (l.text <=> c.text AND l.icon <=> c.icon AND l.active <=> c.active AND l.sort_order <=> c.sort_order
                             AND CAST(l.translations AS JSON) <=> CAST(c.translations AS JSON))"
            ),
        ];
    }

    /**
     * Dedup §4.2a: field_override_rows, custom_without_override (legacy freeze still active for that product until
     * `catalog:dedup-migrate field_overrides`), field_overrides_central_changed (central value differs from the value
     * at approval: "central updated" hint).
     *
     * @return array<string, int|null>
     */
    private function fieldOverrideMetrics(): array
    {
        if (! isset($this->tenantColumns['tenant_field_overrides'])) {
            return ['field_override_rows' => null];
        }

        $o = $this->t('tenant_field_overrides');
        $p = $this->t('products');

        return [
            'field_override_rows' => (int) $this->scalar("SELECT COUNT(*) FROM {$o}"),
            'custom_without_override' => $this->tenantHas('products', 'has_custom_translations') ? (int) $this->scalar(
                "SELECT COUNT(*) FROM {$p} p WHERE p.has_custom_translations = 1 AND p.central_product_id IS NOT NULL
                   AND NOT EXISTS (SELECT 1 FROM {$o} o WHERE o.entity_type = 'central_product' AND o.entity_id = p.central_product_id)"
            ) : null,
            'field_overrides_central_changed' => (int) $this->scalar(
                "SELECT COUNT(*) FROM {$o} o
                   LEFT JOIN " . CentralTable::name('languages') . ' cl ON cl.code = o.locale
                   LEFT JOIN ' . CentralTable::name('translations') . " ct ON ct.translatable_type = ? AND ct.translatable_id = o.entity_id
                        AND ct.language_id = cl.id AND ct.field = o.field
                  WHERE o.entity_type = 'central_product' AND NOT (TRIM(COALESCE(o.central_value_at_approval, '')) <=> TRIM(COALESCE(ct.value, '')))",
                [\App\Models\Product::class],
            ),
        ];
    }

    /**
     * Last `catalog:dedup-migrate` runs of this tenant + section: {prefix}dedup_migrate / dedup_verify (status) and
     * {prefix}dedup_verify_diffs (int; gate e.g. --gate=dedup_verify_diffs:0; null = never verified).
     *
     * @return array<string, mixed>
     */
    private function dedupMetrics(string $section, string $prefix = ''): array
    {
        if (! isset($this->centralColumns['catalog_dedup_runs'])) {
            return [];
        }

        $last = fn (string $step) => $this->db->selectOne(
            'SELECT status, counts FROM `catalog_dedup_runs` WHERE tenant_id = ? AND section = ? AND step = ? ORDER BY id DESC LIMIT 1',
            [(string) $this->tenant->getTenantKey(), $section, $step],
        );
        $migrate = $last('migrate');
        $verify = $last('verify');
        $verifyCounts = $verify ? (array) json_decode((string) $verify->counts, true) : [];

        return [
            $prefix . 'dedup_migrate' => $migrate->status ?? null,
            $prefix . 'dedup_verify' => $verify->status ?? null,
            $prefix . 'dedup_verify_diffs' => $verify && $verify->status === 'done' ? (int) ($verifyCounts['diffs'] ?? 0) : null,
        ];
    }

    /**
     * Rows whose synced `central_visible` differs from central's current publish state (live central rows only;
     * products: soft-deleted central products must be hidden too).
     *
     * @return array<string, int|null>
     */
    private function visibilityMetric(string $table, string $link, string $centralTable, string $visibleSql): array
    {
        if (! $this->tenantHas($table, 'central_visible')) {
            return ['central_visible_mismatch' => null];
        }

        $shouldBeVisible = $table === 'products'
            ? "({$visibleSql}) AND ({$this->centralLive($centralTable)})"
            : $visibleSql;
        $onlyLive = $table === 'products' ? '1 = 1' : $this->centralLive($centralTable);

        return ['central_visible_mismatch' => (int) $this->scalar(
            "SELECT COUNT(*) FROM {$this->t($table)} t
             JOIN " . CentralTable::name($centralTable) . " c ON c.id = t.`{$link}`
             WHERE ({$onlyLive}) AND (t.central_visible = 1) <> ({$shouldBeVisible})"
        )];
    }

    /**
     * Tenant translations of central-linked rows vs central translations of the linked central row, matched by
     * language CODE (language ids differ per DB) + field. Values are compared byte-exact.
     *
     * @return array<string, int>|null
     */
    private function translationParity(string $section, string $table, string $link, string $centralTable, string $centralKey): ?array
    {
        if (! isset($this->tenantColumns['translations'], $this->tenantColumns['languages'], $this->centralColumns['translations'])) {
            return null;
        }

        [$tenantType, $centralType] = self::TRANSLATED[$section];
        $linked = $this->linkedCondition($section, $table, $link);

        $row = $this->db->selectOne(
            "SELECT
                SUM(CASE WHEN ct.id IS NOT NULL AND CAST(ct.value AS BINARY) <=> CAST(tt.value AS BINARY) THEN 1 ELSE 0 END) AS equal,
                SUM(CASE WHEN ct.id IS NOT NULL AND NOT (CAST(ct.value AS BINARY) <=> CAST(tt.value AS BINARY)) THEN 1 ELSE 0 END) AS different,
                SUM(CASE WHEN ct.id IS NULL AND cl.id IS NULL THEN 1 ELSE 0 END) AS tenant_only_language,
                SUM(CASE WHEN ct.id IS NULL AND cl.id IS NOT NULL THEN 1 ELSE 0 END) AS tenant_only_field
            FROM {$this->t('translations')} tt
            JOIN {$this->t($table)} t ON t.id = tt.translatable_id AND tt.translatable_type = ?
            JOIN " . CentralTable::name($centralTable) . " ce ON ce.`{$centralKey}` = t.`{$link}`
            JOIN {$this->t('languages')} tl ON tl.id = tt.language_id
            LEFT JOIN " . CentralTable::name('languages') . ' cl ON cl.code = tl.code
            LEFT JOIN ' . CentralTable::name('translations') . " ct
                   ON ct.translatable_type = ? AND ct.translatable_id = ce.id AND ct.language_id = cl.id AND ct.field = tt.field
            WHERE {$linked}",
            [$tenantType, $centralType],
        );

        return [
            'equal' => (int) $row->equal,
            'different' => (int) $row->different,
            'tenant_only_language' => (int) $row->tenant_only_language,
            'tenant_only_field' => (int) $row->tenant_only_field,
        ];
    }

    /** @return array<string, mixed> */
    private function refMetrics(): array
    {
        $totals = ['null_refs' => 0, 'unmapped_refs' => 0, 'dangling_refs' => 0];
        $tables = [];
        $missing = [];

        $specs = [];
        foreach (array_keys(CatalogRefColumns::TENANT_TABLES) as $table) {
            $specs[$table] = [$this->t($table), $this->tenantColumns[$table] ?? null, '', []];
        }
        foreach (array_keys(CatalogRefColumns::CENTRAL_TABLES) as $table) {
            $specs[$table] = [CentralTable::name($table), $this->centralColumns[$table] ?? null, ' AND t.`tenant_id` = ?', [(string) $this->tenant->getTenantKey()]];
        }

        foreach ($specs as $table => [$from, $columns, $where, $bindings]) {
            if ($columns === null) {
                continue;
            }

            $metrics = ['rows' => (int) $this->scalar("SELECT COUNT(*) FROM {$from} t WHERE 1 = 1{$where}", $bindings)];
            foreach (CatalogRefColumns::specFor($table) as $column => [$entity, $type, $id]) {
                if (! isset($columns[$type], $columns[$id])) {
                    $missing[$table] = true;

                    continue;
                }

                [$entityTable] = CatalogRefColumns::ENTITIES[$entity];
                $central = CentralTable::name(self::REF_CENTRAL_TABLES[$entity]);
                $row = $this->db->selectOne(
                    "SELECT
                        SUM(CASE WHEN t.`{$type}` IS NULL AND t.`{$column}` IS NOT NULL THEN 1 ELSE 0 END) AS null_refs,
                        SUM(CASE WHEN t.`{$type}` IS NULL AND t.`{$column}` IS NOT NULL
                                 AND NOT EXISTS (SELECT 1 FROM {$this->t($entityTable)} e WHERE e.`id` = t.`{$column}`) THEN 1 ELSE 0 END) AS unmapped,
                        SUM(CASE WHEN (t.`{$type}` = 'tenant' AND NOT EXISTS (SELECT 1 FROM {$this->t($entityTable)} e WHERE e.`id` = t.`{$id}`))
                                   OR (t.`{$type}` = 'central' AND NOT EXISTS (SELECT 1 FROM {$central} c WHERE c.`id` = t.`{$id}`)) THEN 1 ELSE 0 END) AS dangling
                     FROM {$from} t WHERE 1 = 1{$where}",
                    $bindings,
                );
                $metrics[$column] = ['null_refs' => (int) $row->null_refs, 'unmapped' => (int) $row->unmapped, 'dangling' => (int) $row->dangling];
                $totals['null_refs'] += (int) $row->null_refs;
                $totals['unmapped_refs'] += (int) $row->unmapped;
                $totals['dangling_refs'] += (int) $row->dangling;
            }
            $tables[$table] = $metrics;
        }

        if (isset($this->tenantColumns['mobile_carts']['items'])) {
            $carts = ['rows' => 0, 'items_without_refs' => 0, 'unmapped' => 0];
            $productIds = array_flip(array_map('intval', array_column(
                $this->db->select("SELECT `id` FROM {$this->t('products')}"),
                'id',
            )));
            foreach ($this->db->select("SELECT `items` FROM {$this->t('mobile_carts')}") as $cart) {
                $carts['rows']++;
                foreach ((array) json_decode((string) $cart->items, true) as $item) {
                    if (is_array($item) && ! isset($item['product_type'])) {
                        $carts['items_without_refs']++;
                        $carts['unmapped'] += isset($productIds[(int) ($item['product_id'] ?? 0)]) ? 0 : 1;
                    }
                }
            }
            $tables['mobile_carts'] = $carts;
            $totals['null_refs'] += $carts['items_without_refs'];
            $totals['unmapped_refs'] += $carts['unmapped'];
        }

        if ($missing !== [] && count($missing) === count(array_filter($specs, fn ($spec) => $spec[1] !== null))) {
            $totals = array_fill_keys(array_keys($totals), null); // nothing migrated yet: no figures, gates skip
        }

        return $totals
            + ($missing !== [] ? ['note' => 'typed columns missing (migration not run): ' . implode(', ', array_keys($missing))] : [])
            + ['tables' => $tables];
    }

    /**
     * Dedup phase 4 category parity (gates, e.g. --gate=pairs_missing_in_copy:0 --gate=dedup_verify_diffs:0):
     *  pairs_missing_in_copy   central category_product pairs of carried products in published, in-scope categories with
     *                          no copy pivot row (sync lag: would appear in central mode → run the products sync first)
     *  stale_central_links     sync-made copy links (`source = central`) central no longer has
     *  sort_overrides_pending  copy rows of a central pair whose sort ≠ central sort without a matching override
     *  links_pending           vendor links (`source = tenant`) on copies central lacks, without an override row
     *  category_overrides_pending copies whose active / featured / order_number differ from the central default or that
     *                          carry a thumb, without an override row
     *  override_rows / pair_override_rows  phase-4 rows (null before the tenant migration)
     *
     * @return array<string, int|null>
     */
    private function categoryParityMetrics(): array
    {
        if (! $this->tenantHas('categories', 'central_category_id') || ! isset($this->tenantColumns['category_product'], $this->centralColumns['category_product'])) {
            return [];
        }

        $t = $this->t('category_product');
        $k = $this->t('categories');
        $p = $this->t('products');
        $o = $this->t('tenant_category_product_overrides');
        $co = $this->t('tenant_category_overrides');
        $ccp = CentralTable::name('category_product');
        $cc = CentralTable::name('categories');
        [$expected, $bindings] = $this->expectedCategories();
        $visible = "c.status = 'published' AND {$this->centralLive('categories')} AND {$expected}";
        $hasPairs = isset($this->tenantColumns['tenant_category_product_overrides']);
        $hasOverrides = isset($this->tenantColumns['tenant_category_overrides']);
        $source = $this->tenantHas('category_product', 'source') ? 't.source' : "'tenant'";
        $isCentral = "({$this->notOwn('p')} AND p.central_product_id IS NOT NULL)";
        $centralPair = "SELECT cp.sort_order FROM {$ccp} cp WHERE cp.category_id = k.central_category_id AND cp.product_id = p.central_product_id AND {$isCentral}";
        $pairOverride = fn (string $extra) => $hasPairs
            ? "EXISTS (SELECT 1 FROM {$o} o WHERE o.category_type = 'central' AND o.category_id = k.central_category_id
                  AND ((o.product_type = 'central' AND {$isCentral} AND o.product_id = p.central_product_id) OR (o.product_type = 'tenant' AND NOT {$isCentral} AND o.product_id = p.id)) {$extra})"
            : '0 = 1';
        $thumbType = $this->db->getPdo()->quote(\App\Models\Tenant\Category::class);

        $row = $this->db->selectOne(
            "SELECT
                SUM(CASE WHEN {$source} = 'central' AND NOT EXISTS ({$centralPair}) THEN 1 ELSE 0 END) AS stale_links,
                SUM(CASE WHEN EXISTS ({$centralPair} AND cp.sort_order <> t.sort_order) AND NOT {$pairOverride('AND o.sort_order = t.sort_order')} THEN 1 ELSE 0 END) AS sort_pending,
                SUM(CASE WHEN {$source} = 'tenant' AND NOT EXISTS ({$centralPair}) AND NOT {$pairOverride("AND o.source = 'tenant'")} THEN 1 ELSE 0 END) AS links_pending
             FROM {$t} t
             JOIN {$k} k ON k.id = t.category_id AND k.central_category_id IS NOT NULL
             JOIN {$p} p ON p.id = t.product_id"
        );

        return [
            'pairs_missing_in_copy' => (int) $this->scalar(
                "SELECT COUNT(*) FROM {$ccp} cp JOIN {$cc} c ON c.id = cp.category_id AND {$visible}
                   JOIN {$p} p ON p.central_product_id = cp.product_id AND {$this->notOwn('p')}
                  WHERE NOT EXISTS (SELECT 1 FROM {$t} t JOIN {$k} k ON k.id = t.category_id
                                     WHERE k.central_category_id = cp.category_id AND t.product_id = p.id)",
                $bindings,
            ),
            'stale_central_links' => (int) $row->stale_links,
            'sort_overrides_pending' => (int) $row->sort_pending,
            'links_pending' => (int) $row->links_pending,
            'category_overrides_pending' => (int) $this->scalar(
                "SELECT COUNT(*) FROM {$k} k JOIN {$cc} c ON c.id = k.central_category_id AND {$this->centralLive('categories')}
                  WHERE (k.active = 0 OR k.featured <> c.is_featured OR k.order_number <> c.order_number
                         OR EXISTS (SELECT 1 FROM {$this->t('files')} f WHERE f.model_type = {$thumbType} AND f.model_id = k.id AND f.`key` = 'thumb'))"
                . ($hasOverrides ? " AND NOT EXISTS (SELECT 1 FROM {$co} co WHERE co.central_category_id = k.central_category_id)" : '')
            ),
            'override_rows' => $hasOverrides ? (int) $this->scalar("SELECT COUNT(*) FROM {$co}") : null,
            'pair_override_rows' => $hasPairs ? (int) $this->scalar("SELECT COUNT(*) FROM {$o}") : null,
        ];
    }

    /**
     * Dedup phase 3 coupon parity (gates, e.g. --gate=applicable_missing:0):
     *  applicable_missing      live central coupons applicable to the tenant (all countries or intersecting its active
     *                          tenant_countries, the syncCoupons() rule) without a copy (sync lag)
     *  copies_not_applicable   copies of live central coupons that no longer apply to the tenant
     *  inactive_central_copies copies of switched-off central coupons (redeemable in sync mode, hidden in central mode)
     *  stale_copies            copy code/type/value/minimum spend/window ≠ central
     *  code_collisions         own coupons whose code a live central coupon also uses (D6: tenant wins)
     *  orders_coupon_refs_pending orders with a coupon whose typed ref / code snapshot is not written yet (NULL before
     *                          the phase-3 tenant migration)
     *
     * @return array<string, int|null>
     */
    private function couponParityMetrics(): array
    {
        if (! $this->tenantHas('coupons', 'central_coupon_id') || ! isset($this->centralColumns['central_coupons'], $this->centralColumns['central_coupon_country'])) {
            return [];
        }

        $t = $this->t('coupons');
        $cc = CentralTable::name('central_coupons');
        $ccc = CentralTable::name('central_coupon_country');
        $tc = CentralTable::name('tenant_countries');
        $live = $this->centralLive('central_coupons');
        $tenantId = (string) $this->tenant->getTenantKey();
        $applicable = "(NOT EXISTS (SELECT 1 FROM {$ccc} x WHERE x.central_coupon_id = c.id)
                        OR EXISTS (SELECT 1 FROM {$ccc} x JOIN {$tc} tc ON tc.country_id = x.country_id AND tc.tenant_id = ? AND tc.is_active = 1
                                    WHERE x.central_coupon_id = c.id))";

        $pending = null;
        if ($this->tenantHas('orders', 'coupon_type') && $this->tenantHas('orders', 'coupon_code')) {
            $pending = (int) $this->scalar(
                "SELECT COUNT(*) FROM {$this->t('orders')} o JOIN {$t} k ON k.id = o.discount_id WHERE o.coupon_type IS NULL OR o.coupon_code IS NULL"
            );
        }

        return [
            'applicable_missing' => (int) $this->scalar(
                "SELECT COUNT(*) FROM {$cc} c WHERE {$live} AND {$applicable} AND NOT EXISTS (SELECT 1 FROM {$t} k WHERE k.central_coupon_id = c.id)",
                [$tenantId],
            ),
            'copies_not_applicable' => (int) $this->scalar(
                "SELECT COUNT(*) FROM {$t} k JOIN {$cc} c ON c.id = k.central_coupon_id WHERE {$live} AND NOT {$applicable}",
                [$tenantId],
            ),
            'inactive_central_copies' => (int) $this->scalar(
                "SELECT COUNT(*) FROM {$t} k JOIN {$cc} c ON c.id = k.central_coupon_id WHERE {$live} AND c.active = 0"
            ),
            'stale_copies' => (int) $this->scalar(
                "SELECT COUNT(*) FROM {$t} k JOIN {$cc} c ON c.id = k.central_coupon_id WHERE {$live}
                   AND NOT (k.code <=> c.code AND k.type <=> c.type AND k.value <=> c.value AND k.minimum_spend <=> c.minimum_spend
                            AND k.start_date <=> c.start_date AND k.end_date <=> c.end_date)"
            ),
            'code_collisions' => (int) $this->scalar(
                "SELECT COUNT(*) FROM {$t} k WHERE k.central_coupon_id IS NULL AND EXISTS (SELECT 1 FROM {$cc} c WHERE {$live} AND c.code = k.code)"
            ),
            'orders_coupon_refs_pending' => $pending,
        ];
    }

    /**
     * Dedup phase 3 flash-sale parity (all 0 before the flash_sales verify can pass):
     *  pairs_missing_in_copy / pairs_extra_in_copy  central sale × carried product pairs vs the copy pivot
     *  stale_copies                                 copy discount / window / active ≠ central
     *  country_mismatch                             copy country set ≠ central set
     *
     * @return array<string, int>
     */
    private function flashSaleParityMetrics(): array
    {
        if (! $this->tenantHas('flash_sales', 'central_flash_sale_id') || ! isset($this->tenantColumns['flash_sale_product'], $this->centralColumns['central_flash_sale_product'])) {
            return [];
        }

        $t = $this->t('flash_sale_product');
        $s = $this->t('flash_sales');
        $p = $this->t('products');
        $fc = $this->t('flash_sale_country');
        $cfs = CentralTable::name('central_flash_sales');
        $cfp = CentralTable::name('central_flash_sale_product');
        $cfc = CentralTable::name('central_flash_sale_country');
        $live = $this->centralLive('central_flash_sales');

        return [
            'pairs_missing_in_copy' => (int) $this->scalar(
                "SELECT COUNT(*) FROM (SELECT DISTINCT cp.central_flash_sale_id, p.id FROM {$cfp} cp
                   JOIN {$cfs} c ON c.id = cp.central_flash_sale_id AND {$live}
                   JOIN {$p} p ON p.central_product_id = cp.product_id
                  WHERE NOT EXISTS (SELECT 1 FROM {$t} t JOIN {$s} s ON s.id = t.flash_sale_id
                                     WHERE s.central_flash_sale_id = cp.central_flash_sale_id AND t.product_id = p.id)) d"
            ),
            'pairs_extra_in_copy' => (int) $this->scalar(
                "SELECT COUNT(*) FROM {$t} t JOIN {$s} s ON s.id = t.flash_sale_id AND s.central_flash_sale_id IS NOT NULL
                   LEFT JOIN {$p} p ON p.id = t.product_id
                  WHERE NOT EXISTS (SELECT 1 FROM {$cfp} cp JOIN {$cfs} c ON c.id = cp.central_flash_sale_id AND {$live}
                                     WHERE cp.central_flash_sale_id = s.central_flash_sale_id AND cp.product_id = p.central_product_id)"
            ),
            'stale_copies' => (int) $this->scalar(
                "SELECT COUNT(*) FROM {$s} s JOIN {$cfs} c ON c.id = s.central_flash_sale_id WHERE {$live}
                   AND NOT (s.discount_percentage <=> c.discount_percentage AND s.start_date <=> c.start_date
                            AND s.end_date <=> c.end_date AND s.active <=> c.active)"
            ),
            'country_mismatch' => (int) $this->scalar(
                "SELECT COUNT(*) FROM {$s} s JOIN {$cfs} c ON c.id = s.central_flash_sale_id WHERE {$live}
                   AND NOT ((SELECT GROUP_CONCAT(x.country_id ORDER BY x.country_id) FROM {$fc} x WHERE x.flash_sale_id = s.id)
                            <=> (SELECT GROUP_CONCAT(y.country_id ORDER BY y.country_id) FROM {$cfc} y WHERE y.central_flash_sale_id = c.id))"
            ),
        ];
    }

    /** SQL for "central row is live" (alias $alias); before the soft-delete migration every row is live. */
    private function centralLive(string $centralTable, string $alias = 'c'): string
    {
        return isset($this->centralColumns[$centralTable]['deleted_at']) ? "{$alias}.`deleted_at` IS NULL" : '1 = 1';
    }

    private function tenantHas(string $table, string $column): bool
    {
        return isset($this->tenantColumns[$table][$column]);
    }

    /** Quoted `<tenant db>`.`table` (tenant DB names contain '-', so quote, don't validate). */
    private function t(string $table): string
    {
        return '`' . str_replace('`', '``', $this->tenantDb) . '`.`' . $table . '`';
    }

    private function scalar(string $sql, array $bindings = []): mixed
    {
        $row = (array) $this->db->selectOne($sql, $bindings);

        return reset($row);
    }

    /** @return array<string, array<string, true>> */
    private function columnsOf(string $schema): array
    {
        $columns = [];
        foreach ($this->db->select('SELECT TABLE_NAME AS t, COLUMN_NAME AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ?', [$schema]) as $row) {
            $columns[$row->t][$row->c] = true;
        }

        return $columns;
    }

    /**
     * Runs $callback with the central session in READ ONLY transaction mode (MySQL error 1792 on any write, also for
     * cross-schema writes into tenant DBs), restoring READ WRITE afterwards.
     */
    private function readOnly(callable $callback): mixed
    {
        if ($this->db->getDriverName() !== 'mysql') {
            return $callback();
        }

        $this->db->statement('SET SESSION TRANSACTION READ ONLY');

        try {
            return $callback();
        } finally {
            $this->db->statement('SET SESSION TRANSACTION READ WRITE');
        }
    }
}
