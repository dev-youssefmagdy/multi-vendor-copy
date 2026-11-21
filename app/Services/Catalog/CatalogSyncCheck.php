<?php

namespace App\Services\Catalog;

use App\Enums\EmailTemplateType;
use App\Enums\PaymentGatewayType;
use App\Enums\ProductEditRequestStatus;
use App\Models\Tenant;
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
        $sections = $sections === [] ? CatalogSource::SECTIONS : array_values(array_intersect(CatalogSource::SECTIONS, $sections));

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

            $result['sections'][$section] = ['mode' => CatalogSource::for($tenant, $section)] + $metrics;
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function checkSection(string $section): array
    {
        $spec = self::SPECS[$section];
        if ($spec === null) {
            return ['note' => 'no central link (keyed by name); mode only'];
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
            'categories' => $this->visibilityMetric('categories', 'central_category_id', 'categories', "c.status = 'published'"),
            'badges' => ['duplicate_texts' => (int) $this->scalar(
                "SELECT COUNT(*) FROM (SELECT text FROM {$this->t('product_badges')} GROUP BY text HAVING COUNT(*) > 1) d"
            )],
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

        return $metrics;
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
