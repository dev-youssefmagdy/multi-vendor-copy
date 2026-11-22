<?php

namespace App\Repositories\Tenant\Catalog;

use App\Models\Tenant;
use App\Models\Tenant\Catalog\MergedCategory;
use App\Models\Tenant\Category;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogRef;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use App\Support\Catalog\CategoryKey;
use App\Support\Catalog\CentralTable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Merged category read layer (dedup plan §3.3, phase 4; design in §11 "Backend — Phase 4 design").
 *
 * Categories   = central categories in the tenant's scope (`tenants.category_ids` roots + descendants, or all), live
 *                (not soft-deleted) and published (the sync's `central_visible` rule), with `tenant_category_overrides`
 *                (active / featured / order_number / thumb) — UNION ALL — own tenant categories (`central_category_id
 *                IS NULL`). Synced copies are ignored. `id` = CategoryKey (central id | own id + 1e9), `ref` = c:/t:.
 * Translations = central values by language CODE for active central languages ∪ tenant `central_category` rows for
 *                languages central lacks ∪ own categories' rows (MergedCategoryTranslation).
 * Membership   = central `category_product` pairs of carried central products (sort override / hidden) ∪ vendor links
 *                to central categories (`tenant_category_product_overrides.source = tenant`) ∪ `category_product` rows
 *                of own categories.
 *
 * Only used when the tenant's `catalog_source.categories` is `central` (enabled()); every caller keeps the synced
 * tables otherwise. Read-only: writes go through CategoryAssignments. Never writes central (invariant 1).
 */
final class CategoryCatalog
{
    public const OVERRIDES = 'tenant_category_overrides';

    public const PAIR_OVERRIDES = 'tenant_category_product_overrides';

    /** translatable_type of tenant rows for languages central lacks, keyed by the CENTRAL category id (§4.2). */
    public const CENTRAL_TRANSLATION_TYPE = 'central_category';

    /** File model_type of a vendor thumb uploaded for a central category (model_id = central id). */
    public const CENTRAL_FILE_TYPE = 'central_category';

    /** Global CacheVersion tag bumped on central category / category-product writes (invariant 8). */
    public const CACHE_TAG = 'CentralCategory';

    /** Columns of categoriesSql(), in order. */
    public const COLUMNS = [
        'id', 'ref', 'source', 'source_id', 'central_category_id', 'parent_id', 'order_number', 'active', 'featured',
        'central_visible', 'thumb_file_id', 'created_at', 'updated_at',
    ];

    private static bool $warned = false;

    /** @var array<string, mixed> per-request memo (scope ids, ...) */
    private static array $memo = [];

    /** Merged reads are on for the current (or given) tenant: section flipped AND the phase-4 tenant schema exists. */
    public static function enabled(?Tenant $tenant = null): bool
    {
        $tenant ??= tenant();
        if (! $tenant instanceof Tenant || ! CatalogSource::isCentral($tenant, 'categories')) {
            return false;
        }

        if (self::schemaReady()) {
            return true;
        }

        if (! self::$warned) {
            self::$warned = true;
            Log::warning('catalog_source.categories is central but the tenant DB lacks the phase-4 category schema; reading synced categories.', [
                'tenant' => $tenant->getTenantKey(),
            ]);
        }

        return false;
    }

    public static function schemaReady(?ConnectionInterface $db = null): bool
    {
        $db ??= DB::connection('tenant');

        return CatalogRefColumns::hasTable($db, self::OVERRIDES)
            && CatalogRefColumns::hasTable($db, self::PAIR_OVERRIDES)
            && CatalogRefColumns::has($db, 'categories', 'parent_type')
            && CatalogRefColumns::ready($db, 'category_product');
    }

    /** Category query for readers: merged rows in central mode, the synced Eloquent model otherwise. */
    public static function query(): Builder
    {
        return self::enabled() ? MergedCategory::query() : Category::query();
    }

    public static function flushMemo(): void
    {
        self::$memo = [];
    }

    private static function db(): ConnectionInterface
    {
        return DB::connection('tenant');
    }

    // ── Lookups ─────────────────────────────────────────────────────────────

    /** A merged category by key (int / numeric string) or ref ("c:12" / "t:3"); null when not in the merged set. */
    public function find(mixed $value): ?MergedCategory
    {
        $key = CategoryKey::keyOf($value);

        return $key === null ? null : MergedCategory::query()->whereKey($key)->first();
    }

    /** @return Collection<int, MergedCategory> every merged category (any `active`) with names, ordered like the panel. */
    public function all(): Collection
    {
        return MergedCategory::query()->with('translations.language')->orderBy('order_number')->orderBy('id')->get();
    }

    /** @return list<int> keys of the merged set */
    public function keys(): array
    {
        return array_map('intval', MergedCategory::query()->pluck('id')->all());
    }

    /**
     * Flat depth-aware picker list (same shape as TenantPanelRepository::categoryTreeOptions(): id = key). A child whose
     * parent is not in the merged set (centrally hidden) is listed as a root (Phase -1 R1).
     *
     * @return list<array{id:int,name:string,depth:int}>
     */
    public function treeOptions(): array
    {
        $rows = $this->all()->sortBy([['parent_id', 'asc'], ['id', 'asc']])->values();
        $visible = $rows->pluck('id')->flip();
        $byParent = [];
        foreach ($rows as $row) {
            $parent = $row->parent_id !== null && isset($visible[$row->parent_id]) ? (int) $row->parent_id : 0;
            $byParent[$parent][] = $row;
        }

        $flat = [];
        $walk = function (int $parent, int $depth) use (&$walk, &$flat, $byParent) {
            foreach ($byParent[$parent] ?? [] as $row) {
                $flat[] = ['id' => (int) $row->id, 'name' => $this->label($row), 'depth' => $depth];
                $walk((int) $row->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $flat;
    }

    /** @return array<int, string> key => name, like TenantPanelRepository::categoryOptions(). */
    public function options(?int $keepKey = null): array
    {
        $options = [];
        foreach ($this->all()->sortBy('id') as $row) {
            $options[(int) $row->id] = $this->label($row);
        }

        if ($keepKey !== null && ! isset($options[$keepKey])) {
            $options[$keepKey] = __('Category #:id', ['id' => $keepKey]) . ' (' . __('Hidden by platform') . ')';
        }

        return $options;
    }

    public function label(MergedCategory $category): string
    {
        return (string) ($category->translationValue('name') ?? __('Category #:id', ['id' => $category->id]));
    }

    /** @return list<int> the key plus every descendant key in the merged set (any `active`). */
    public function withDescendants(int $key): array
    {
        $children = [];
        foreach (MergedCategory::query()->get(['id', 'parent_id']) as $row) {
            if ($row->parent_id !== null) {
                $children[(int) $row->parent_id][] = (int) $row->id;
            }
        }

        $ids = [$key];
        $queue = [$key];
        while ($queue !== []) {
            foreach ($children[array_shift($queue)] ?? [] as $child) {
                if (! in_array($child, $ids, true)) {
                    $ids[] = $child;
                    $queue[] = $child;
                }
            }
        }

        return $ids;
    }

    /**
     * Ordered tenant product ids of one category (membership order: sort_order, then product id).
     *
     * @return list<int>
     */
    public function productIds(int $key): array
    {
        return array_map('intval', self::db()->query()
            ->fromRaw('(' . self::membershipSql() . ') as m')
            ->where('m.category_key', $key)
            ->orderBy('m.sort_order')
            ->orderBy('m.product_id')
            ->pluck('m.product_id')
            ->all());
    }

    /**
     * A `category_id` filter / input value (key, legacy numeric id or ref) → the id of the CURRENT mode's id space:
     * central mode = CategoryKey; sync mode = tenant id (a ref "c:N" maps to the copy of central N, "t:N" to N), so
     * links rewritten to refs by `catalog:dedup-migrate categories` work in both modes. null = no / invalid filter.
     */
    public static function filterId(mixed $value): ?int
    {
        if ($value === null || $value === '' || is_bool($value)) {
            return null;
        }
        if (self::enabled()) {
            return CategoryKey::keyOf(is_string($value) ? $value : (is_int($value) ? $value : null));
        }

        if (is_numeric($value)) {
            return (int) $value > 0 ? (int) $value : null;
        }

        $ref = is_string($value) ? CatalogRef::tryParse($value) : null;
        if ($ref === null) {
            return null;
        }
        if ($ref->isTenant()) {
            return $ref->id;
        }

        $id = Category::query()->where('central_category_id', $ref->id)->orderBy('id')->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Tenant language codes central has ACTIVE: a central category's texts in these languages are central's (read-only
     * for the vendor, the sync overwrote them too); other tenant languages are the vendor's (tenant-only, §4.2).
     *
     * @return list<string>
     */
    public static function centralLocales(): array
    {
        return array_values(array_map('strval', self::db()->table('languages as tl')
            ->join(CentralTable::qualified('languages') . ' as cl', 'cl.code', '=', 'tl.code')
            ->where('cl.is_active', true)
            ->pluck('tl.code')
            ->all()));
    }

    /** Cache-key suffix for category caches: merged and synced category objects must never share a key. */
    public static function cacheSuffix(): string
    {
        return self::enabled() ? ':merged' : '';
    }

    // ── Scope ───────────────────────────────────────────────────────────────

    /**
     * Central category ids the tenant carries (its chosen roots + live descendants), or null = all (no roots chosen).
     * Same rule as TenantCatalogSyncService::syncCategories() / expandCategoryIds().
     *
     * @return list<int>|null
     */
    public static function scopeIds(?Tenant $tenant = null): ?array
    {
        $tenant ??= tenant();
        $roots = $tenant instanceof Tenant ? array_values(array_filter(array_map('intval', (array) $tenant->category_ids))) : [];
        if ($roots === []) {
            return null;
        }

        $key = 'scope|' . spl_object_id(app()) . '|' . $tenant->getTenantKey() . '|' . implode(',', $roots) . '|' . CacheVersion::get(self::CACHE_TAG);

        return self::$memo[$key] ??= (function () use ($roots) {
            $deleted = CatalogRefColumns::has(self::db(), 'categories', 'deleted_at', CentralTable::database()) ? ' WHERE `deleted_at` IS NULL' : '';
            $children = [];
            foreach (self::db()->select('SELECT `id`, `parent_id` FROM ' . CentralTable::name('categories') . $deleted) as $row) {
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
            sort($ids);

            return $ids;
        })();
    }

    /** SQL condition "central category (alias $alias) is in the merged set": live, published, in scope. */
    public static function centralVisible(string $alias = 'cc', ?ConnectionInterface $db = null): string
    {
        $db ??= self::db();
        $parts = ["{$alias}.`status` = 'published'"];
        if (CatalogRefColumns::has($db, 'categories', 'deleted_at', CentralTable::database())) {
            $parts[] = "{$alias}.`deleted_at` IS NULL";
        }
        $scope = self::scopeIds();
        if ($scope !== null) {
            $parts[] = "{$alias}.`id` IN (" . implode(',', array_map('intval', $scope ?: [0])) . ')';
        }

        return '(' . implode(' AND ', $parts) . ')';
    }

    // ── SQL ─────────────────────────────────────────────────────────────────

    /** Quoted string literal (class names contain backslashes). */
    private static function lit(string $value, ?ConnectionInterface $db = null): string
    {
        return ($db ?? self::db())->getPdo()->quote($value);
    }

    /**
     * SQL of the merged categories (identifiers + integer literals only, no bindings). Columns: COLUMNS.
     */
    public static function categoriesSql(?ConnectionInterface $db = null): string
    {
        $db ??= self::db();
        $cc = CentralTable::name('categories');
        $o = '`' . self::OVERRIDES . '`';
        $off = CategoryKey::OWN_OFFSET;
        $visible = self::centralVisible('cc', $db);
        $scope = self::scopeIds();
        $parentInScope = $scope === null
            ? 'cc.`parent_id`'
            : 'CASE WHEN cc.`parent_id` IN (' . implode(',', array_map('intval', $scope ?: [0])) . ') THEN cc.`parent_id` END';
        $centralDeleted = CatalogRefColumns::has($db, 'categories', 'deleted_at', CentralTable::database())
            ? "(SELECT pp.`id` FROM {$cc} pp WHERE pp.`id` = cc.`parent_id` AND pp.`deleted_at` IS NULL)"
            : 'cc.`parent_id`';
        $ownType = self::lit(Category::class, $db);

        return "SELECT cc.`id` AS `id`, CONCAT('c:', cc.`id`) AS `ref`, 'central' AS `source`, cc.`id` AS `source_id`,
                    cc.`id` AS `central_category_id`,
                    CASE WHEN {$centralDeleted} IS NULL THEN NULL ELSE {$parentInScope} END AS `parent_id`,
                    COALESCE(o.`order_number`, cc.`order_number`) AS `order_number`, COALESCE(o.`active`, 1) AS `active`,
                    COALESCE(o.`featured`, cc.`is_featured`) AS `featured`, 1 AS `central_visible`, o.`thumb_file_id` AS `thumb_file_id`,
                    cc.`created_at` AS `created_at`, GREATEST(cc.`updated_at`, COALESCE(o.`updated_at`, cc.`updated_at`)) AS `updated_at`
                FROM {$cc} cc
                LEFT JOIN {$o} o ON o.`central_category_id` = cc.`id`
                WHERE {$visible}
            UNION ALL
            SELECT c.`id` + {$off}, CONCAT('t:', c.`id`), 'tenant', c.`id`, NULL,
                    CASE WHEN c.`parent_type` = 'central' THEN c.`catalog_parent_id`
                         WHEN c.`parent_type` = 'tenant' THEN c.`catalog_parent_id` + {$off}
                         WHEN c.`parent_id` IS NOT NULL THEN (SELECT IFNULL(pc.`central_category_id`, pc.`id` + {$off}) FROM `categories` pc WHERE pc.`id` = c.`parent_id`)
                         ELSE NULL END,
                    c.`order_number`, c.`active`, c.`featured`, 1,
                    (SELECT MIN(f.`id`) FROM `files` f WHERE f.`model_type` = {$ownType} AND f.`model_id` = c.`id` AND f.`key` = 'thumb'),
                    c.`created_at`, c.`updated_at`
                FROM `categories` c
                WHERE c.`central_category_id` IS NULL";
    }

    /**
     * SQL of the merged category translations. Columns: id, translatable_type (MergedCategory), translatable_id (category KEY), language_id (TENANT
     * language id), field, value. D3: a central slug equal to an own category's slug (same language) gets `-c`.
     */
    public static function translationsSql(?ConnectionInterface $db = null): string
    {
        $db ??= self::db();
        $ct = CentralTable::name('translations');
        $cl = CentralTable::name('languages');
        $off = CategoryKey::OWN_OFFSET;
        $centralType = self::lit(\App\Models\Category::class, $db);
        $ownType = self::lit(Category::class, $db);
        $tenantOnly = self::lit(self::CENTRAL_TRANSLATION_TYPE, $db);
        $merged = self::lit(MergedCategory::class, $db);
        $ownSlug = "EXISTS (SELECT 1 FROM `translations` ot JOIN `categories` oc ON oc.`id` = ot.`translatable_id` AND oc.`central_category_id` IS NULL
                    WHERE ot.`translatable_type` = {$ownType} AND ot.`field` = 'slug' AND ot.`language_id` = tl.`id` AND ot.`value` = TRIM(ct.`value`))";

        return "SELECT CONCAT('c:', ct.`translatable_id`, ':', tl.`id`, ':', ct.`field`) AS `id`, {$merged} AS `translatable_type`,
                    ct.`translatable_id` AS `translatable_id`, tl.`id` AS `language_id`, ct.`field` AS `field`,
                    CASE WHEN ct.`field` = 'slug' AND {$ownSlug} THEN CONCAT(TRIM(ct.`value`), '-c') ELSE TRIM(ct.`value`) END AS `value`
                FROM {$ct} ct
                JOIN {$cl} cl ON cl.`id` = ct.`language_id` AND cl.`is_active` = 1
                JOIN `languages` tl ON tl.`code` = cl.`code`
                WHERE ct.`translatable_type` = {$centralType} AND TRIM(ct.`value`) <> ''
            UNION ALL
            SELECT CONCAT('x:', t.`id`), {$merged}, t.`translatable_id`, t.`language_id`, t.`field`, t.`value`
                FROM `translations` t
                JOIN `languages` tl ON tl.`id` = t.`language_id`
                WHERE t.`translatable_type` = {$tenantOnly}
                  AND NOT EXISTS (SELECT 1 FROM {$cl} x WHERE x.`code` = tl.`code` AND x.`is_active` = 1)
            UNION ALL
            SELECT CONCAT('t:', t.`id`), {$merged}, t.`translatable_id` + {$off}, t.`language_id`, t.`field`, t.`value`
                FROM `translations` t
                JOIN `categories` c ON c.`id` = t.`translatable_id` AND c.`central_category_id` IS NULL
                WHERE t.`translatable_type` = {$ownType}";
    }

    /**
     * SQL of the merged product↔category membership. Columns: category_key, product_id (TENANT product id: products
     * are still synced until phase 5), sort_order, pair_source (central | tenant), category_type, category_id (source
     * id), product_type, catalog_product_id, created_at, updated_at.
     */
    public static function membershipSql(?ConnectionInterface $db = null): string
    {
        $db ??= self::db();
        $cc = CentralTable::name('categories');
        $ccp = CentralTable::name('category_product');
        $o = '`' . self::PAIR_OVERRIDES . '`';
        $off = CategoryKey::OWN_OFFSET;
        $visible = self::centralVisible('cc', $db);
        $hasOwnFlag = CatalogRefColumns::has($db, 'products', 'is_own_product');
        $notOwn = fn (string $a) => $hasOwnFlag ? "COALESCE({$a}.`is_own_product`, 0) = 0" : '1 = 1';
        $isCentral = "({$notOwn('p')} AND p.`central_product_id` IS NOT NULL)";
        $productType = "CASE WHEN {$isCentral} THEN 'central' ELSE 'tenant' END";
        $catalogProductId = "CASE WHEN {$isCentral} THEN p.`central_product_id` ELSE p.`id` END";

        return "SELECT cp.`category_id` AS `category_key`, p.`id` AS `product_id`, COALESCE(o.`sort_order`, cp.`sort_order`) AS `sort_order`,
                    'central' AS `pair_source`, 'central' AS `category_type`, cp.`category_id` AS `category_id`,
                    'central' AS `product_type`, cp.`product_id` AS `catalog_product_id`, cp.`created_at` AS `created_at`, cp.`updated_at` AS `updated_at`
                FROM {$ccp} cp
                JOIN {$cc} cc ON cc.`id` = cp.`category_id` AND {$visible}
                JOIN `products` p ON p.`central_product_id` = cp.`product_id` AND {$notOwn('p')}
                LEFT JOIN {$o} o ON o.`category_type` = 'central' AND o.`category_id` = cp.`category_id`
                    AND o.`product_type` = 'central' AND o.`product_id` = cp.`product_id`
                WHERE COALESCE(o.`hidden`, 0) = 0
            UNION ALL
            SELECT o.`category_id`, p.`id`, COALESCE(o.`sort_order`, 0), 'tenant', 'central', o.`category_id`, 'central', o.`product_id`, o.`created_at`, o.`updated_at`
                FROM {$o} o
                JOIN {$cc} cc ON cc.`id` = o.`category_id` AND {$visible}
                JOIN `products` p ON p.`central_product_id` = o.`product_id` AND {$notOwn('p')}
                WHERE o.`category_type` = 'central' AND o.`product_type` = 'central' AND o.`source` = 'tenant' AND o.`hidden` = 0
                  AND NOT EXISTS (SELECT 1 FROM {$ccp} x WHERE x.`category_id` = o.`category_id` AND x.`product_id` = o.`product_id`)
            UNION ALL
            SELECT o.`category_id`, p.`id`, COALESCE(o.`sort_order`, 0), 'tenant', 'central', o.`category_id`, 'tenant', p.`id`, o.`created_at`, o.`updated_at`
                FROM {$o} o
                JOIN {$cc} cc ON cc.`id` = o.`category_id` AND {$visible}
                JOIN `products` p ON p.`id` = o.`product_id`
                WHERE o.`category_type` = 'central' AND o.`product_type` = 'tenant' AND o.`source` = 'tenant' AND o.`hidden` = 0
            UNION ALL
            SELECT t.`category_id` + {$off}, t.`product_id`, t.`sort_order`, 'tenant', 'tenant', t.`category_id`, {$productType}, {$catalogProductId}, t.`created_at`, t.`updated_at`
                FROM `category_product` t
                JOIN `categories` c ON c.`id` = t.`category_id` AND c.`central_category_id` IS NULL
                JOIN `products` p ON p.`id` = t.`product_id`";
    }
}
