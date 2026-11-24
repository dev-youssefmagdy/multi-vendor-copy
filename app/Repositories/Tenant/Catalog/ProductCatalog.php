<?php

namespace App\Repositories\Tenant\Catalog;

use App\Models\Tenant;
use App\Services\Tenant\TenantPricingService;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use App\Support\Catalog\CentralTable;
use App\Support\Catalog\ProductKey;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Merged product read layer (dedup plan §3.3, phase 5): central live products (published, not soft-deleted)
 * + `tenant_product_overrides` (adoption = row exists; NULL column = inherit central) UNION ALL own tenant products
 * (`central_product_id IS NULL`). Synced copies are ignored. Used only when `catalog_source.products` is `central`
 * (enabled()); every caller keeps the synced tables otherwise.
 *
 * Pricing is NEVER a second formula: the SQL carries raw central values + overrides, the per-country price map is
 * derived with App\Services\Tenant\TenantPricingService::forCatalog() (priceMap()). The only SQL price is the
 * `sort_price` cache column (default row = central price + profit), identical to TenantPricingService::compute() for
 * the "default" key (shipping 0). Order snapshots are never recomputed from this layer (ORDER_FORMULAS.md).
 *
 * Read-only: writes go through adopt() / setOverride() / release() on the tenant override tables only (invariant 1).
 */
final class ProductCatalog
{
    public const OVERRIDES = 'tenant_product_overrides';

    public const VARIANT_OVERRIDES = 'tenant_product_variant_overrides';

    /** Global CacheVersion tag bumped on any central product / variant write (invariant 8). */
    public const CACHE_TAG = 'CentralProduct';

    /** Override columns a tenant may set (everything else is central, read-only). */
    public const PRODUCT_OVERRIDE_FIELDS = [
        'active', 'featured', 'order_number', 'profit', 'price_override', 'stock_override', 'weight_grams_override',
        'fixed_shipping_costs_override', 'allowed_country_ids', 'return_policy_override', 'is_returnable',
        'return_window_days', 'return_fee', 'return_video_required', 'return_conditions',
        'social_posts', 'social_image_b64', 'ai_price_data',
    ];

    public const VARIANT_OVERRIDE_FIELDS = ['active', 'profit', 'margin_percentage', 'price_override', 'stock_override', 'thumbnail_path'];

    /** Sections that must be `central` before `products` may be (flag-flip order, plan §6). */
    public const REQUIRED_SECTIONS = ['badges', 'labels', 'flash-sales', 'categories'];

    private static bool $warned = false;

    /**
     * SQL: the product of an order line, in the same key space as `products.id` of the current read.
     * Sync mode: the tenant product id (the line's own, else its variant's). Central mode: the merged product key of
     * the line's typed pair (central lines carry no tenant product id, and tenant ids are not central ids).
     */
    public static function orderLineKeySql(string $orderItems = 'order_items', string $variants = 'product_variants'): string
    {
        return self::enabled()
            ? ProductKey::sqlPairKey("{$orderItems}.`product_type`", "{$orderItems}.`catalog_product_id`")
            : "COALESCE({$orderItems}.product_id, {$variants}.product_id)";
    }

    public static function enabled(?Tenant $tenant = null): bool
    {
        $tenant ??= tenant();
        if (! $tenant instanceof Tenant || ! CatalogSource::isCentral($tenant, 'products')) {
            return false;
        }
        // Merged products key every relation by the product KEY: badges / labels / flash sales / categories must read
        // through their merged layers too (phases 2-4), otherwise their synced pivots would point at missing copies.
        foreach (self::REQUIRED_SECTIONS as $section) {
            if (! CatalogSource::isCentral($tenant, $section)) {
                if (! self::$warned) {
                    self::$warned = true;
                    Log::warning("catalog_source.products is central but [{$section}] is not; reading synced products.", ['tenant' => $tenant->getTenantKey()]);
                }

                return false;
            }
        }

        if (self::schemaReady()) {
            return true;
        }
        if (! self::$warned) {
            self::$warned = true;
            Log::warning('catalog_source.products is central but the tenant DB lacks the phase-5 override tables; reading synced products.', [
                'tenant' => $tenant->getTenantKey(),
            ]);
        }

        return false;
    }

    public static function schemaReady(?ConnectionInterface $db = null): bool
    {
        $db ??= DB::connection('tenant');

        return CatalogRefColumns::hasTable($db, self::OVERRIDES) && CatalogRefColumns::hasTable($db, self::VARIANT_OVERRIDES);
    }

    public static function db(): ConnectionInterface
    {
        return DB::connection('tenant');
    }

    /** SQL condition "central product (alias) is in the merged set": published and live (not soft-deleted). */
    public static function centralVisible(string $alias = 'cp', ?ConnectionInterface $db = null): string
    {
        $db ??= self::db();
        $parts = ["{$alias}.`status` = 'published'"];
        if (CatalogRefColumns::has($db, 'products', 'deleted_at', CentralTable::database())) {
            $parts[] = "{$alias}.`deleted_at` IS NULL";
        }
        // E2E fixtures are visible only to the dedicated E2E tenants (rollout QA step 4).
        if ($hidden = \App\Support\Catalog\E2eFixtures::hiddenProductIds()) {
            $parts[] = \App\Support\Catalog\E2eFixtures::notInSql("{$alias}.`id`", $hidden);
        }

        return '(' . implode(' AND ', $parts) . ')';
    }

    /** SQL: JSON profit object {"default": {profit_type, profit_value}} seeded with the tenant percentage (TenantPricingService::seedProfit). */
    private static function seedProfitJson(string $pct): string
    {
        return "JSON_OBJECT('default', JSON_OBJECT('profit_type', 'percentage', 'profit_value', {$pct}))";
    }

    /**
     * SQL: effective profit JSON of a merged row. Seed (tenant %) <- override profit rows <- vendor-typed margin (percentage
     * default row) <- vendor-typed price (fixed default row = price - base), the same precedence as
     * TenantPricingService::forCatalog(); country rows of the override are kept as stored.
     */
    private static function profitJsonSql(string $alias, string $base, string $pct, bool $hasMargin): string
    {
        $seed = self::seedProfitJson($pct);
        $merged = "JSON_MERGE_PATCH({$seed}, COALESCE({$alias}.`profit`, JSON_OBJECT()))";
        $price = "JSON_MERGE_PATCH({$merged}, JSON_OBJECT('default', JSON_OBJECT('profit_type', 'fixed', 'profit_value', GREATEST(0, ROUND({$alias}.`price_override` - {$base}, 2)))))";
        if ($hasMargin) {
            $margin = "JSON_MERGE_PATCH({$merged}, JSON_OBJECT('default', JSON_OBJECT('profit_type', 'percentage', 'profit_value', GREATEST(0, ROUND({$alias}.`margin_percentage`, 2)))))";

            return "CASE WHEN {$alias}.`margin_percentage` IS NOT NULL THEN {$margin} WHEN {$alias}.`price_override` IS NOT NULL THEN {$price} ELSE {$merged} END";
        }

        return "CASE WHEN {$alias}.`price_override` IS NOT NULL THEN {$price} ELSE {$merged} END";
    }

    /**
     * SQL: the fixed shipping cost map of a CENTRAL product / variant (alias), exactly the sync's centralShippingCosts():
     * its own non-empty `fixed_shipping_costs`, else weight x price_per_gram of every active rule, else NULL.
     */
    private static function centralCostsSql(string $alias): string
    {
        $fsc = CentralTable::name('fixed_shipping_costs');

        return "CASE WHEN {$alias}.`fixed_shipping_costs` IS NOT NULL AND JSON_LENGTH({$alias}.`fixed_shipping_costs`) > 0 THEN {$alias}.`fixed_shipping_costs`
                     WHEN COALESCE({$alias}.`weight_grams`, 0) > 0 THEN (SELECT JSON_OBJECTAGG(CAST(f.`country_id` AS CHAR), ROUND(f.`price_per_gram` * {$alias}.`weight_grams`, 2))
                                                                           FROM {$fsc} f WHERE f.`is_active` = 1)
                     ELSE NULL END";
    }

    /** SQL: default-row price from a profit JSON (alias.profit) and base, the "default" key of TenantPricingService::compute(). */
    private static function defaultPriceSql(string $alias, string $base): string
    {
        $type = "JSON_UNQUOTE(JSON_EXTRACT({$alias}.`profit`, '\$.default.profit_type'))";
        $val = "CAST(JSON_UNQUOTE(JSON_EXTRACT({$alias}.`profit`, '\$.default.profit_value')) AS DECIMAL(14,4))";

        // Same rounding as TenantPricingService::compute(): the profit AMOUNT is rounded first, then base + amount.
        return "ROUND({$base} + CASE WHEN {$type} = 'fixed' THEN ROUND({$val}, 2) ELSE ROUND({$base} * {$val} / 100, 2) END, 2)";
    }

    /**
     * SQL of the merged products, EVERY column a Tenant\Product reader uses (MergedProduct is a drop-in). `id` = ProductKey
     * (central id | own id + 1e9), `ref` ("c:12" / "t:3"), `source`, `source_id`, `central_product_id`. Central rows: live +
     * adopted (override row), hidden ones (draft / archived / soft-deleted centrally) kept with `central_visible` = 0 so the model's
     * `centralVisible` scope hides them exactly like a synced copy (order history / stock restore use withoutGlobalScope); `price` = {"default": default_price} (the per-country key of the visitor is
     * derived in PHP / StorefrontRepository through TenantPricingService), `profit` = effective profit JSON,
     * `base_price` = central sale/base price. `sort_price` = `default_price` (sort / filter cache). Own rows pass through.
     */
    public static function productsSql(float $tenantPct = 0.0, ?ConnectionInterface $db = null): string
    {
        $db ??= self::db();
        $cp = CentralTable::name('products');
        $pc = CentralTable::name('product_country');
        $o = '`' . self::OVERRIDES . '`';
        $off = ProductKey::OWN_OFFSET;
        $visible = self::centralVisible('cp', $db);
        $pct = number_format(max(0.0, $tenantPct), 4, '.', '');
        $base = 'CASE WHEN cp.`sale_price` > 0 THEN cp.`sale_price` ELSE cp.`base_price` END';
        $profit = self::profitJsonSql('o', $base, $pct, false);
        $costs = self::centralCostsSql('cp');
        $default = self::defaultPriceSql('i', 'i.`base_price`');
        $ordersCount = "(SELECT COUNT(DISTINCT oi.`order_id`) FROM `order_items` oi WHERE oi.`product_type` = 'central' AND oi.`catalog_product_id` = cp.`id`)";

        return "SELECT i.`id`, i.`ref`, i.`source`, i.`source_id`, i.`central_product_id`, i.`sku`, i.`slug`, i.`active`, i.`central_visible`,
                    i.`allowed_country_ids`, i.`featured`, i.`order_number`, i.`is_own_product`, i.`is_tenant_owned`, i.`requires_shipping`,
                    i.`stock`, i.`min_stock`, i.`manage_stock`, i.`is_taxable`, i.`weight_grams`,
                    JSON_OBJECT('default', {$default}) AS `price`, {$default} AS `default_price`, NULL AS `sale_price`, NULL AS `cost_price`,
                    i.`profit`, i.`fixed_shipping_costs`, i.`return_policy_override`, i.`is_returnable`, i.`return_window_days`, i.`return_fee`,
                    i.`return_video_required`, i.`return_conditions`, i.`orders_count`, i.`base_price`, {$default} AS `sort_price`,
                    NULL AS `files_key`, i.`social_posts`, i.`social_image_b64`, i.`ai_price_data`, 0 AS `has_custom_translations`, 0 AS `needs_ai_translation`, i.`created_at`, i.`updated_at`
                FROM (SELECT cp.`id` AS `id`, CONCAT('c:', cp.`id`) AS `ref`, 'central' AS `source`, cp.`id` AS `source_id`, cp.`id` AS `central_product_id`,
                        cp.`sku` AS `sku`, cp.`slug` AS `slug`, COALESCE(o.`active`, 1) AS `active`, CASE WHEN {$visible} THEN 1 ELSE 0 END AS `central_visible`,
                        COALESCE(o.`allowed_country_ids`, (SELECT JSON_ARRAYAGG(pc.`country_id`) FROM {$pc} pc WHERE pc.`product_id` = cp.`id`)) AS `allowed_country_ids`,
                        COALESCE(o.`featured`, 0) AS `featured`, COALESCE(o.`order_number`, cp.`order_number`) AS `order_number`,
                        0 AS `is_own_product`, 1 AS `is_tenant_owned`, cp.`requires_shipping` AS `requires_shipping`,
                        COALESCE(o.`stock_override`, cp.`stock`) AS `stock`, cp.`min_stock` AS `min_stock`, cp.`manage_stock` AS `manage_stock`,
                        cp.`is_taxable` AS `is_taxable`, COALESCE(o.`weight_grams_override`, cp.`weight_grams`) AS `weight_grams`,
                        {$profit} AS `profit`, COALESCE(o.`fixed_shipping_costs_override`, {$costs}) AS `fixed_shipping_costs`,
                        o.`return_policy_override` AS `return_policy_override`, o.`is_returnable` AS `is_returnable`, o.`return_window_days` AS `return_window_days`,
                        o.`return_fee` AS `return_fee`, o.`return_video_required` AS `return_video_required`, o.`return_conditions` AS `return_conditions`,
                        o.`social_posts` AS `social_posts`, o.`social_image_b64` AS `social_image_b64`, o.`ai_price_data` AS `ai_price_data`,
                        {$ordersCount} AS `orders_count`, {$base} AS `base_price`, cp.`created_at` AS `created_at`,
                        GREATEST(cp.`updated_at`, o.`updated_at`) AS `updated_at`
                    FROM {$cp} cp JOIN {$o} o ON o.`central_product_id` = cp.`id`) i
            UNION ALL
            SELECT t.`id` + {$off}, CONCAT('t:', t.`id`), 'tenant', t.`id`, NULL, t.`sku`, t.`slug`, t.`active`, 1, t.`allowed_country_ids`, t.`featured`,
                    t.`order_number`, t.`is_own_product`, t.`is_tenant_owned`, t.`requires_shipping`, t.`stock`, t.`min_stock`, t.`manage_stock`,
                    t.`is_taxable`, t.`weight_grams`, t.`price`, t.`default_price`, t.`sale_price`, t.`cost_price`, t.`profit`, t.`fixed_shipping_costs`,
                    t.`return_policy_override`, t.`is_returnable`, t.`return_window_days`, t.`return_fee`, t.`return_video_required`,
                    t.`return_conditions`, t.`orders_count`, t.`default_price`, t.`default_price`, t.`id`, t.`social_posts`, t.`social_image_b64`, t.`ai_price_data`, t.`has_custom_translations`, t.`needs_ai_translation`,
                    t.`created_at`, t.`updated_at`
                FROM `products` t WHERE t.`central_product_id` IS NULL";
    }

    /**
     * SQL of the merged variants, every column a Tenant\ProductVariant reader uses. `id` = ProductKey of the variant (central
     * id | own id + 1e9), `product_id` = merged PRODUCT key, `central_product_variant_id`, `option_ids` (JSON), `real_price`
     * (central variant price), `profit` (effective, same precedence as forCatalog), `default_sell_price`, `sell_price`
     * ({"default": ...}), `stock` (override else central), `active`. Variants of adopted, visible central products (variant
     * not soft-deleted) + variants of own products.
     */
    public static function variantsSql(float $tenantPct = 0.0, ?ConnectionInterface $db = null): string
    {
        $db ??= self::db();
        $cv = CentralTable::name('product_variants');
        $cp = CentralTable::name('products');
        $cvo = CentralTable::name('product_variant_option');
        $vo = '`' . self::VARIANT_OVERRIDES . '`';
        $po = '`' . self::OVERRIDES . '`';
        $off = ProductKey::OWN_OFFSET;
        $vLive = CatalogRefColumns::has($db, 'product_variants', 'deleted_at', CentralTable::database()) ? ' AND cv.`deleted_at` IS NULL' : '';
        $pct = number_format(max(0.0, $tenantPct), 4, '.', '');
        $profit = self::profitJsonSql('vo', 'cv.`price`', $pct, true);
        $default = self::defaultPriceSql('i', 'i.`real_price`');
        $vcosts = self::centralCostsSql('cv');
        $pcosts = self::centralCostsSql('cp');

        return "SELECT i.`id`, i.`ref`, i.`source`, i.`source_id`, i.`product_id`, i.`central_product_variant_id`, i.`title`, i.`sku`, i.`weight_grams`,
                    i.`option_ids`, i.`real_price`, i.`fixed_shipping_costs`, JSON_OBJECT('default', {$default}) AS `sell_price`, {$default} AS `default_sell_price`, i.`profit`,
                    i.`stock`, i.`stock_override`, i.`margin_percentage`, i.`thumbnail_path`, i.`active`, {$default} AS `sort_price`, i.`created_at`, i.`updated_at`
                FROM (SELECT cv.`id` AS `id`, CONCAT('c:', cv.`id`) AS `ref`, 'central' AS `source`, cv.`id` AS `source_id`, cv.`product_id` AS `product_id`,
                        cv.`id` AS `central_product_variant_id`, cv.`title` AS `title`, cv.`sku` AS `sku`, cv.`weight_grams` AS `weight_grams`,
                        (SELECT JSON_ARRAYAGG(x.`variation_option_id`) FROM {$cvo} x WHERE x.`product_variant_id` = cv.`id`) AS `option_ids`,
                        cv.`price` AS `real_price`,
                        COALESCE(CASE WHEN {$vcosts} IS NOT NULL THEN {$vcosts} END, COALESCE(po.`fixed_shipping_costs_override`, {$pcosts})) AS `fixed_shipping_costs`, {$profit} AS `profit`, COALESCE(vo.`stock_override`, cv.`stock`) AS `stock`, vo.`stock_override` AS `stock_override`,
                        vo.`margin_percentage` AS `margin_percentage`, vo.`thumbnail_path` AS `thumbnail_path`,
                        COALESCE(vo.`active`, CASE WHEN cv.`status` = 'active' THEN 1 ELSE 0 END) AS `active`, cv.`created_at` AS `created_at`,
                        GREATEST(cv.`updated_at`, COALESCE(vo.`updated_at`, cv.`updated_at`)) AS `updated_at`
                    FROM {$cv} cv JOIN {$cp} cp ON cp.`id` = cv.`product_id` JOIN {$po} po ON po.`central_product_id` = cp.`id`
                    LEFT JOIN {$vo} vo ON vo.`central_variant_id` = cv.`id`
                    WHERE 1 = 1{$vLive}) i
            UNION ALL
            SELECT t.`id` + {$off}, CONCAT('t:', t.`id`), 'tenant', t.`id`, t.`product_id` + {$off}, NULL, t.`title`, t.`sku`, t.`weight_grams`, t.`option_ids`,
                    t.`real_price`, NULL, t.`sell_price`, t.`default_sell_price`, t.`profit`, t.`stock`, NULL, t.`margin_percentage`, t.`thumbnail_path`, t.`active`,
                    t.`default_sell_price`, t.`created_at`, t.`updated_at`
                FROM `product_variants` t JOIN `products` p ON p.`id` = t.`product_id` AND p.`central_product_id` IS NULL";
    }

    public const FIELD_OVERRIDE_ENTITY = 'central_product';

    /** translatable_type of tenant rows for languages central lacks, keyed by the CENTRAL product id (§4.2). */
    public const CENTRAL_TRANSLATION_TYPE = 'central_product';

    private static function lit(string $value, ?ConnectionInterface $db = null): string
    {
        return ($db ?? self::db())->getPdo()->quote($value);
    }

    /**
     * SQL of the merged product translations. Columns: id, translatable_type (Tenant\Product, the morph class MergedProduct
     * reports), translatable_id (product KEY), language_id (TENANT language id), field, value. Central values by language CODE
     * (active central languages) with approved field overrides (§4.2a) winning per (field, locale); tenant-only-language rows of
     * central products (type `central_product`); own products' rows.
     */
    public static function translationsSql(?ConnectionInterface $db = null): string
    {
        $db ??= self::db();
        $ct = CentralTable::name('translations');
        $cl = CentralTable::name('languages');
        $off = ProductKey::OWN_OFFSET;
        $centralType = self::lit(\App\Models\Product::class, $db);
        $ownType = self::lit(\App\Models\Tenant\Product::class, $db);
        $tenantOnly = self::lit(self::CENTRAL_TRANSLATION_TYPE, $db);
        $entity = self::lit(self::FIELD_OVERRIDE_ENTITY, $db);
        $fo = "(SELECT fo.`value` FROM `tenant_field_overrides` fo WHERE fo.`entity_type` = {$entity} AND fo.`entity_id` = ct.`translatable_id`
                    AND fo.`field` = ct.`field` AND fo.`locale_key` = cl.`code` LIMIT 1)";

        return "SELECT CONCAT('c:', ct.`translatable_id`, ':', tl.`id`, ':', ct.`field`) AS `id`, {$ownType} AS `translatable_type`,
                    ct.`translatable_id` AS `translatable_id`, tl.`id` AS `language_id`, ct.`field` AS `field`,
                    COALESCE({$fo}, TRIM(ct.`value`)) AS `value`
                FROM {$ct} ct
                JOIN {$cl} cl ON cl.`id` = ct.`language_id` AND cl.`is_active` = 1
                JOIN `languages` tl ON tl.`code` = cl.`code`
                WHERE ct.`translatable_type` = {$centralType} AND TRIM(ct.`value`) <> '' AND " . self::adoptedSql('ct.`translatable_id`') . "
            UNION ALL
            SELECT CONCAT('x:', t.`id`), {$ownType}, t.`translatable_id`, t.`language_id`, t.`field`, t.`value`
                FROM `translations` t
                JOIN `languages` tl ON tl.`id` = t.`language_id`
                WHERE t.`translatable_type` = {$tenantOnly}
                  AND NOT EXISTS (SELECT 1 FROM {$cl} x WHERE x.`code` = tl.`code` AND x.`is_active` = 1)
            UNION ALL
            SELECT CONCAT('t:', t.`id`), {$ownType}, t.`translatable_id` + {$off}, t.`language_id`, t.`field`, t.`value`
                FROM `translations` t
                JOIN `products` p ON p.`id` = t.`translatable_id` AND p.`central_product_id` IS NULL
                WHERE t.`translatable_type` = {$ownType}";
    }

    /** SQL of the merged variant translations (title): central variants by language code, own variants' rows. */
    public static function variantTranslationsSql(?ConnectionInterface $db = null): string
    {
        $db ??= self::db();
        $ct = CentralTable::name('translations');
        $cl = CentralTable::name('languages');
        $cv = CentralTable::name('product_variants');
        $off = ProductKey::OWN_OFFSET;
        $centralType = self::lit(\App\Models\ProductVariant::class, $db);
        $ownType = self::lit(\App\Models\Tenant\ProductVariant::class, $db);

        return "SELECT CONCAT('c:', ct.`translatable_id`, ':', tl.`id`, ':', ct.`field`) AS `id`, {$ownType} AS `translatable_type`,
                    ct.`translatable_id` AS `translatable_id`, tl.`id` AS `language_id`, ct.`field` AS `field`, TRIM(ct.`value`) AS `value`
                FROM {$ct} ct
                JOIN {$cl} cl ON cl.`id` = ct.`language_id` AND cl.`is_active` = 1
                JOIN `languages` tl ON tl.`code` = cl.`code`
                JOIN {$cv} cv ON cv.`id` = ct.`translatable_id`
                WHERE ct.`translatable_type` = {$centralType} AND TRIM(ct.`value`) <> '' AND " . self::adoptedSql('cv.`product_id`') . "
            UNION ALL
            SELECT CONCAT('t:', t.`id`), {$ownType}, t.`translatable_id` + {$off}, t.`language_id`, t.`field`, t.`value`
                FROM `translations` t
                JOIN `product_variants` v ON v.`id` = t.`translatable_id` AND v.`central_product_variant_id` IS NULL
                JOIN `products` p ON p.`id` = v.`product_id` AND p.`central_product_id` IS NULL
                WHERE t.`translatable_type` = {$ownType}";
    }

    /** SQL of the ratings keyed by the merged product KEY (typed pair of `product_rates`). Columns: product_rates.* + product_key. */
    public static function ratesSql(): string
    {
        return 'SELECT r.*, ' . ProductKey::sqlPairKey('r.`product_type`', 'r.`catalog_product_id`') . ' AS `product_key` FROM `product_rates` r';
    }

    /**
     * Per-country price map of one merged central row, through TenantPricingService (no inline formula).
     *
     * @param  array<string, mixed>|null  $costs   fixed_shipping_costs (override, else central)
     * @param  array<string, mixed>|null  $profit  override profit JSON
     * @return array{prices: array<string, float>, default: float, profit: array<string, array<string, mixed>>}
     */
    public function priceMap(float $base, ?array $costs, ?array $profit, float $tenantPct, ?float $overrideDefault = null): array
    {
        return (new TenantPricingService())->forCatalog($base, $costs, $profit, $tenantPct, $overrideDefault);
    }

    // ── Query entry points + storefront SQL ─────────────────────────────────

    /** Product query for readers: merged rows in central mode, the synced Eloquent model otherwise. */
    public static function query(): \Illuminate\Database\Eloquent\Builder
    {
        return self::enabled() ? \App\Models\Tenant\Catalog\MergedProduct::query() : \App\Models\Tenant\Product::query();
    }

    public static function variantQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return self::enabled() ? \App\Models\Tenant\Catalog\MergedProductVariant::query() : \App\Models\Tenant\ProductVariant::query();
    }

    /** SQL table expression for `product_variants` in raw subqueries: the real table, or the merged derived table. */
    public static function variantsFrom(): string
    {
        if (! self::enabled()) {
            return 'product_variants';
        }
        $tenant = tenant();

        return '(' . self::variantsSql($tenant ? (float) ($tenant->profit_percentage ?? 0) : 0.0) . ')';
    }

    /**
     * SQL: the price a visitor in `$country` sees for a merged row (alias with columns source, profit, fixed_shipping_costs,
     * price JSON, base / default columns), i.e. the sync's stored per-country `price` / `sell_price` key. Own rows: the stored
     * JSON key, then default, then the scalar. Central rows: the key exists when the country has a shipping cost or a profit
     * row (as the sync seeds them); then round(base + round(profit amount) + shipping, 2) like TenantPricingService::compute(),
     * otherwise the default-row price.
     */
    public static function countryPriceSql(string $alias, string $priceJsonCol, string $baseCol, string $defaultCol, ?int $country): string
    {
        if (! $country) {
            return "COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT({$alias}.`{$priceJsonCol}`, '\$.default')) AS DECIMAL(12,4)), {$alias}.`{$defaultCol}`)";
        }
        $c = (int) $country;
        $own = "COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT({$alias}.`{$priceJsonCol}`, '\$.\"{$c}\"')) AS DECIMAL(12,4)), "
            . "CAST(JSON_UNQUOTE(JSON_EXTRACT({$alias}.`{$priceJsonCol}`, '\$.default')) AS DECIMAL(12,4)), {$alias}.`{$defaultCol}`)";
        $cost = "JSON_EXTRACT({$alias}.`fixed_shipping_costs`, '\$.\"{$c}\"')";
        $row = "JSON_EXTRACT({$alias}.`profit`, '\$.\"{$c}\"')";
        $type = "COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$alias}.`profit`, '\$.\"{$c}\".profit_type')), JSON_UNQUOTE(JSON_EXTRACT({$alias}.`profit`, '\$.default.profit_type')))";
        $val = "CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$alias}.`profit`, '\$.\"{$c}\".profit_value')), JSON_UNQUOTE(JSON_EXTRACT({$alias}.`profit`, '\$.default.profit_value'))) AS DECIMAL(14,4))";
        $central = "CASE WHEN {$cost} IS NULL AND {$row} IS NULL THEN {$alias}.`{$defaultCol}`
                    ELSE ROUND({$alias}.`{$baseCol}` + CASE WHEN {$type} = 'fixed' THEN ROUND({$val}, 2) ELSE ROUND({$alias}.`{$baseCol}` * {$val} / 100, 2) END
                         + COALESCE(CAST(JSON_UNQUOTE({$cost}) AS DECIMAL(14,4)), 0), 2) END";

        return "CASE WHEN {$alias}.`source` = 'tenant' THEN {$own} ELSE {$central} END";
    }

    // ── Shared SQL fragments for the other merged repositories (one place, no join invents its own logic) ──

    /** SQL: "central product id (expression) is adopted by this tenant" (an override row exists). */
    public static function adoptedSql(string $centralIdExpr): string
    {
        $sql = "EXISTS (SELECT 1 FROM `" . self::OVERRIDES . "` ad WHERE ad.`central_product_id` = {$centralIdExpr})";
        // E2E fixtures are visible only to the dedicated E2E tenants (rollout QA step 4).
        if ($hidden = \App\Support\Catalog\E2eFixtures::hiddenProductIds()) {
            $sql = "({$sql} AND " . \App\Support\Catalog\E2eFixtures::notInSql($centralIdExpr, $hidden) . ')';
        }

        return $sql;
    }

    /** SQL: merged key of a typed product pair stored in two columns (alias-qualified). */
    public static function pairKeySql(string $typeColumn, string $idColumn): string
    {
        return \App\Support\Catalog\ProductKey::sqlPairKey($typeColumn, $idColumn);
    }

    /**
     * SQL: the typed pair columns match the OUTER merged product row (derived table aliased `products`, columns `source`,
     * `source_id`), e.g. pairMatchSql('fsp.`product_type`', 'fsp.`catalog_product_id`').
     */
    public static function pairMatchSql(string $typeColumn, string $idColumn, string $outer = 'products'): string
    {
        return "({$typeColumn} = {$outer}.`source` AND {$idColumn} = {$outer}.`source_id`)";
    }

    /**
     * Order line identity of a merged product / variant (ORDER_FORMULAS.md §4): tenant ids only for OWN rows (central rows
     * have no tenant row: `product_id` / `product_variant_id` NULL), always the typed pair. Empty for synced models, so the
     * sync path is unchanged. Money columns are never touched.
     *
     * @return array<string, mixed>
     */
    public static function lineColumns(\App\Models\Tenant\Product $product, ?\App\Models\Tenant\ProductVariant $variant): array
    {
        if (! $product instanceof \App\Models\Tenant\Catalog\MergedProduct) {
            return [];
        }
        $cols = [
            'product_id' => $product->isCentral() ? null : (int) $product->source_id,
            'product_type' => (string) $product->source,
            'catalog_product_id' => (int) $product->source_id,
        ];
        if ($variant instanceof \App\Models\Tenant\Catalog\MergedProductVariant) {
            $cols += [
                'product_variant_id' => $variant->source === 'central' ? null : (int) $variant->source_id,
                'variant_type' => (string) $variant->source,
                'catalog_variant_id' => (int) $variant->source_id,
            ];
        }

        return $cols;
    }

    /**
     * Columns identifying a product on a tenant reference row (favorites / product_rates / order_items) from a merged KEY
     * (or ref): tenant `product_id` only for OWN products, always the typed pair. Null for an invalid key.
     *
     * @return array{product_id: int|null, product_type: string, catalog_product_id: int}|null
     */
    public static function refColumnsForKey(mixed $key): ?array
    {
        $ref = ProductKey::parse($key);
        if ($ref === null) {
            return null;
        }

        return ['product_id' => $ref->isTenant() ? $ref->id : null, 'product_type' => $ref->source, 'catalog_product_id' => $ref->id];
    }

    /** Constrains a tenant reference table to ONE merged product (by key) through its typed pair columns. */
    public static function wherePairKey(\Illuminate\Database\Query\Builder $query, int $key, string $typeColumn = 'product_type', string $idColumn = 'catalog_product_id'): \Illuminate\Database\Query\Builder
    {
        $ref = ProductKey::ref($key);

        return $ref === null ? $query->whereRaw('1 = 0') : $query->where($typeColumn, $ref->source)->where($idColumn, $ref->id);
    }

    /** Excludes the given merged keys (own + central mixed) from a reference table through its typed pair columns. */
    public static function whereNotPairKeys(\Illuminate\Database\Query\Builder $query, array $keys, string $typeColumn = 'product_type', string $idColumn = 'catalog_product_id'): \Illuminate\Database\Query\Builder
    {
        $keys = array_values(array_unique(array_map('intval', $keys)));

        return $keys === [] ? $query : $query->whereRaw('COALESCE(' . ProductKey::sqlPairKey($typeColumn, $idColumn) . ', 0) NOT IN (' . implode(',', $keys) . ')');
    }

    /**
     * Typed refs of merged product KEYS for the writers: key => [id (tenant product id for OWN rows, NULL for central),
     * product_type, catalog_product_id]. Keys that do not resolve are dropped.
     *
     * @param  list<int>  $keys
     * @return array<int, array{id: int|null, product_type: string, catalog_product_id: int}>
     */
    public static function refsForKeys(array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $ref = ProductKey::ref((int) $key);
            if ($ref !== null) {
                $out[(int) $key] = ['id' => $ref->isTenant() ? $ref->id : null, 'product_type' => $ref->source, 'catalog_product_id' => $ref->id];
            }
        }

        return $out;
    }

    /**
     * Replaces the products of an OWN flash sale by merged product KEYS (typed pairs, no tenant product row needed for
     * central products). Returns true when a row was added, false when a key was removed (see attachFlashSaleProduct()).
     *
     * @param  list<int>  $keys
     */
    public function syncFlashSaleProducts(int $flashSaleId, array $keys): void
    {
        $db = self::db();
        $refs = self::refsForKeys($keys);
        $existing = $db->table('flash_sale_product')->where('flash_sale_id', $flashSaleId)->get(['product_type', 'catalog_product_id'])
            ->map(fn ($r) => ProductKey::fromPair($r->product_type, $r->catalog_product_id))->filter()->all();

        $db->transaction(function () use ($db, $flashSaleId, $refs, $keys, $existing) {
            self::whereNotPairKeys($db->table('flash_sale_product')->where('flash_sale_id', $flashSaleId), $keys)->delete();
            if ($keys === []) {
                $db->table('flash_sale_product')->where('flash_sale_id', $flashSaleId)->delete();
            }
            foreach ($refs as $key => $ref) {
                if (in_array($key, $existing, true)) {
                    continue;
                }
                $db->table('flash_sale_product')->insert([
                    'flash_sale_id' => $flashSaleId, 'product_id' => $ref['id'],
                    'product_type' => $ref['product_type'], 'catalog_product_id' => $ref['catalog_product_id'],
                    'flash_sale_type' => 'tenant', 'catalog_flash_sale_id' => $flashSaleId,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
        CacheVersion::bumpMany(['FlashSale', 'Product']);
    }

    /** Toggles one product on an own flash sale (central mode); returns the new attached state. */
    public function toggleFlashSaleProduct(int $flashSaleId, int $key): bool
    {
        $db = self::db();
        $q = self::wherePairKey($db->table('flash_sale_product')->where('flash_sale_id', $flashSaleId), $key);
        if ($q->exists()) {
            self::wherePairKey($db->table('flash_sale_product')->where('flash_sale_id', $flashSaleId), $key)->delete();
            CacheVersion::bumpMany(['FlashSale', 'Product']);

            return false;
        }
        $current = $db->table('flash_sale_product')->where('flash_sale_id', $flashSaleId)->get(['product_type', 'catalog_product_id'])
            ->map(fn ($r) => ProductKey::fromPair($r->product_type, $r->catalog_product_id))->filter()->all();
        $this->syncFlashSaleProducts($flashSaleId, [...$current, $key]);

        return true;
    }

    /**
     * Storefront sort position of a product, by KEY in central mode (central row: `tenant_product_overrides.order_number`;
     * own row: the real tenant row). Sync mode: the tenant row, exactly like before.
     */
    public function setOrderNumber(int $idOrKey, int $position): void
    {
        if (! self::enabled()) {
            \App\Models\Tenant\Product::query()->whereKey($idOrKey)->update(['order_number' => $position]);

            return;
        }
        $ref = ProductKey::ref($idOrKey);
        if ($ref === null) {
            return;
        }
        $ref->isCentral()
            ? $this->setOverride($ref->id, ['order_number' => $position])
            : \App\Models\Tenant\Product::query()->whereKey($ref->id)->update(['order_number' => $position]);
        $this->bump();
    }

    /**
     * Removes every reference row of an OWN product by typed pair when it is deleted (belt and braces next to the FK
     * cascades: favourites, ratings, badge / category / flash-sale links and category overrides).
     */
    public static function purgeOwnProductRefs(int $ownProductId): void
    {
        $db = self::db();
        foreach (['favorites', 'product_rates', 'product_badge_product', 'category_product', 'flash_sale_product'] as $table) {
            if (CatalogRefColumns::has($db, $table, 'catalog_product_id')) {
                $db->table($table)->where('product_type', 'tenant')->where('catalog_product_id', $ownProductId)->delete();
            }
        }
        if (CatalogRefColumns::hasTable($db, CategoryCatalog::PAIR_OVERRIDES)) {
            $db->table(CategoryCatalog::PAIR_OVERRIDES)->where('product_type', 'tenant')->where('product_id', $ownProductId)->delete();
        }
        if (CatalogRefColumns::hasTable($db, BadgeCatalog::OVERRIDES)) {
            $db->table(BadgeCatalog::OVERRIDES)->where('product_type', 'tenant')->where('product_id', $ownProductId)->delete();
        }
        CacheVersion::bumpMany(['Product', 'ProductVariant', 'ProductBadge', 'Category', 'FlashSale']);
    }

    // ── Writes (tenant override tables only) ────────────────────────────────

    /** Adoption = insert the override row (idempotent). Returns true when newly adopted. */
    public function adopt(int $centralProductId): bool
    {
        if (in_array($centralProductId, \App\Support\Catalog\E2eFixtures::hiddenProductIds(), true)) {
            return false; // E2E fixture: never adopted by a real tenant
        }
        $created = self::db()->table(self::OVERRIDES)->insertOrIgnore([
            'central_product_id' => $centralProductId, 'adopted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]) > 0;
        $this->bump();

        return $created;
    }

    /** @param array<string, mixed> $fields only PRODUCT_OVERRIDE_FIELDS keys; others are ignored (central stays read-only) */
    public function setOverride(int $centralProductId, array $fields): void
    {
        $data = array_intersect_key($fields, array_flip(self::PRODUCT_OVERRIDE_FIELDS));
        foreach (['profit', 'fixed_shipping_costs_override', 'allowed_country_ids', 'social_posts', 'ai_price_data'] as $json) {
            if (array_key_exists($json, $data) && is_array($data[$json])) {
                $data[$json] = json_encode($data[$json]);
            }
        }
        $this->adopt($centralProductId);
        if ($data !== []) {
            self::db()->table(self::OVERRIDES)->where('central_product_id', $centralProductId)->update($data + ['updated_at' => now()]);
        }
        $this->bump();
    }

    /** @param array<string, mixed> $fields only VARIANT_OVERRIDE_FIELDS keys */
    public function setVariantOverride(int $centralVariantId, int $centralProductId, array $fields): void
    {
        $data = array_intersect_key($fields, array_flip(self::VARIANT_OVERRIDE_FIELDS));
        if (array_key_exists('profit', $data) && is_array($data['profit'])) {
            $data['profit'] = json_encode($data['profit']);
        }
        self::db()->table(self::VARIANT_OVERRIDES)->updateOrInsert(
            ['central_variant_id' => $centralVariantId],
            $data + ['central_product_id' => $centralProductId, 'updated_at' => now(), 'created_at' => now()],
        );
        $this->bump();
    }

    /**
     * Stock move for a CENTRAL-ref line in central mode (dedup phase 5): when the vendor set a `stock_override` it is
     * the tenant's own stock and moves (floored at 0, same clamp as StockService::stockExpression); with no override the
     * line follows central stock, which StockService::moveCentralStock already moves. Returns true when an override
     * row absorbed the move (the caller then must not touch central stock).
     *
     * @param  int  $direction  -1 decrement, +1 restore
     */
    public function moveOverrideStock(int $centralProductId, ?int $centralVariantId, int $qty, int $direction): bool
    {
        if ($qty < 1) {
            return false;
        }
        $db = self::db();
        $delta = $direction < 0 ? -$qty : $qty;
        $table = $centralVariantId !== null ? self::VARIANT_OVERRIDES : self::OVERRIDES;
        $key = $centralVariantId !== null ? 'central_variant_id' : 'central_product_id';
        $id = $centralVariantId ?? $centralProductId;

        $affected = $db->table($table)->where($key, $id)->whereNotNull('stock_override')
            ->update(['stock_override' => $db->raw('GREATEST(0, `stock_override` + ' . (int) $delta . ')'), 'updated_at' => now()]);
        if ($affected > 0) {
            $this->bump();
        }

        return $affected > 0;
    }

    /** Removes the adoption (override rows of the product and its variants); central is untouched. */
    public function release(int $centralProductId): void
    {
        self::db()->table(self::VARIANT_OVERRIDES)->where('central_product_id', $centralProductId)->delete();
        self::db()->table(self::OVERRIDES)->where('central_product_id', $centralProductId)->delete();
        $this->bump();
    }

    /** Tenant-scoped tags (the global CentralProduct tag is bumped by CentralCatalogSyncObserver). */
    private function bump(): void
    {
        CacheVersion::bumpMany(['Product', 'ProductVariant']);
    }
}
