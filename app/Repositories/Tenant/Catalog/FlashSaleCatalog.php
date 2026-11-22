<?php

namespace App\Repositories\Tenant\Catalog;

use App\Models\CentralFlashSale;
use App\Models\Tenant;
use App\Models\Tenant\Catalog\MergedFlashSale;
use App\Models\Tenant\Product;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use App\Support\Catalog\CentralTable;
use App\Support\OrderPricing;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Flash sales, dedup verdict DROP (plan §4, phase 3). While the tenant's `catalog_source.flash-sales` is `central`
 * (and the phase-1 typed pivot columns exist), flash sales are read as
 *
 *  - central `central_flash_sales` read live through the tenant connection (CentralTable), not soft-deleted, having at
 *    least one product the tenant carries (the rule TenantCatalogSyncService::syncFlashSales() used: tenant
 *    `products.central_product_id` = central pivot `product_id`, any product row, own flag not checked);
 *    countries = central `central_flash_sale_country` as is (the sync copied the set untrimmed);
 *  - UNION ALL tenant own sales (`flash_sales.central_flash_sale_id IS NULL`) with their pivot / country rows.
 *
 * Legacy central copies are ignored (deleted in phase 7). Membership rows carry the TENANT product id (products are
 * still synced until phase 5). Read-only; own sales are still written by the panel (TenantPanelService).
 * Sync mode: nothing here is used, every reader keeps the synced tables.
 */
final class FlashSaleCatalog
{
    /** Global CacheVersion tag bumped on central flash-sale writes (invariant 8). */
    public const CACHE_TAG = 'CentralFlashSale';

    /** Columns of salesSql() (membershipSql() adds `product_id`). */
    public const COLUMNS = [
        'id', 'source', 'source_id', 'central_flash_sale_id', 'discount_percentage', 'start_date', 'end_date',
        'active', 'banner_image', 'created_at', 'updated_at',
    ];

    private static bool $warned = false;

    public static function enabled(?Tenant $tenant = null): bool
    {
        $tenant ??= tenant();
        if (! $tenant instanceof Tenant || ! CatalogSource::isCentral($tenant, 'flash-sales')) {
            return false;
        }

        if (self::schemaReady()) {
            return true;
        }

        if (! self::$warned) {
            self::$warned = true;
            Log::warning('catalog_source.flash-sales is central but the tenant DB lacks the phase-1 flash-sale pivot refs; reading synced flash sales.', [
                'tenant' => $tenant->getTenantKey(),
            ]);
        }

        return false;
    }

    public static function schemaReady(?ConnectionInterface $db = null): bool
    {
        $db ??= DB::connection('tenant');

        return CatalogRefColumns::has($db, 'flash_sales', 'central_flash_sale_id')
            && CatalogRefColumns::ready($db, 'flash_sale_product');
    }

    /**
     * order_items.discount_source of a sale of either kind: merged rows carry `source`, synced copies (Tenant\FlashSale)
     * keep the central_flash_sale_id rule. Same value for the same sale in both modes.
     */
    public static function discountSource(object $flashSale): string
    {
        $source = $flashSale instanceof Model ? $flashSale->getAttribute('source') : ($flashSale->source ?? null);
        if (in_array($source, ['central', 'tenant'], true)) {
            return $source === 'central' ? OrderPricing::SOURCE_CENTRAL : OrderPricing::SOURCE_VENDOR;
        }

        $centralId = $flashSale instanceof Model ? $flashSale->getAttribute('central_flash_sale_id') : ($flashSale->central_flash_sale_id ?? null);

        return $centralId !== null ? OrderPricing::SOURCE_CENTRAL : OrderPricing::SOURCE_VENDOR;
    }

    // ── SQL ─────────────────────────────────────────────────────────────────────────────────────────────────────

    /** SQL "central sale row is live" for alias $alias. */
    public static function centralLive(ConnectionInterface $db, string $alias = 'cfs'): string
    {
        return CatalogRefColumns::has($db, 'central_flash_sales', 'deleted_at', CentralTable::database())
            ? "{$alias}.`deleted_at` IS NULL"
            : '1 = 1';
    }

    private static function centralColumns(string $a): string
    {
        return "CONCAT('c:', {$a}.`id`) AS `id`, 'central' AS `source`, {$a}.`id` AS `source_id`, {$a}.`id` AS `central_flash_sale_id`,
                {$a}.`discount_percentage` AS `discount_percentage`, {$a}.`start_date` AS `start_date`, {$a}.`end_date` AS `end_date`,
                {$a}.`active` AS `active`, {$a}.`banner_image` AS `banner_image`, {$a}.`created_at` AS `created_at`, {$a}.`updated_at` AS `updated_at`";
    }

    private static function tenantColumns(string $a): string
    {
        return "CONCAT('t:', {$a}.`id`) AS `id`, 'tenant' AS `source`, {$a}.`id` AS `source_id`, NULL AS `central_flash_sale_id`,
                {$a}.`discount_percentage` AS `discount_percentage`, {$a}.`start_date` AS `start_date`, {$a}.`end_date` AS `end_date`,
                {$a}.`active` AS `active`, {$a}.`banner_image` AS `banner_image`, {$a}.`created_at` AS `created_at`, {$a}.`updated_at` AS `updated_at`";
    }

    /** Merged sales (one row per sale), identifiers only. */
    public static function salesSql(?ConnectionInterface $db = null): string
    {
        $db ??= DB::connection('tenant');
        $cfs = CentralTable::name('central_flash_sales');
        $cfp = CentralTable::name('central_flash_sale_product');

        return "SELECT " . self::centralColumns('cfs') . "
                  FROM {$cfs} cfs
                 WHERE " . self::centralLive($db) . "
                   AND EXISTS (SELECT 1 FROM {$cfp} cp " . (ProductCatalog::enabled() ? '' : "JOIN `products` p ON p.`central_product_id` = cp.`product_id`") . "
                                WHERE cp.`central_flash_sale_id` = cfs.`id`" . (ProductCatalog::enabled() ? ' AND ' . ProductCatalog::adoptedSql('cp.`product_id`') : '') . ")
                UNION ALL
                SELECT " . self::tenantColumns('fs') . "
                  FROM `flash_sales` fs
                 WHERE fs.`central_flash_sale_id` IS NULL";
    }

    /** Merged sale × product rows (`product_id` = TENANT product id), identifiers only. */
    public static function membershipSql(?ConnectionInterface $db = null): string
    {
        $db ??= DB::connection('tenant');
        $cfs = CentralTable::name('central_flash_sales');
        $cfp = CentralTable::name('central_flash_sale_product');

        if (ProductCatalog::enabled()) {
            // Phase 5: product_id = merged key; central pairs need an adoption row, own sales use the typed pair.
            return "SELECT DISTINCT " . self::centralColumns('cfs') . ", cp.`product_id` AS `product_id`
                      FROM {$cfp} cp
                      JOIN {$cfs} cfs ON cfs.`id` = cp.`central_flash_sale_id` AND " . self::centralLive($db) . "
                     WHERE " . ProductCatalog::adoptedSql('cp.`product_id`') . "
                    UNION ALL
                    SELECT " . self::tenantColumns('fs') . ", " . ProductCatalog::pairKeySql('fsp.`product_type`', 'fsp.`catalog_product_id`') . " AS `product_id`
                      FROM `flash_sale_product` fsp
                      JOIN `flash_sales` fs ON fs.`id` = fsp.`flash_sale_id` AND fs.`central_flash_sale_id` IS NULL
                     WHERE fsp.`catalog_product_id` IS NOT NULL";
        }

        return "SELECT DISTINCT " . self::centralColumns('cfs') . ", p.`id` AS `product_id`
                  FROM {$cfp} cp
                  JOIN {$cfs} cfs ON cfs.`id` = cp.`central_flash_sale_id` AND " . self::centralLive($db) . "
                  JOIN `products` p ON p.`central_product_id` = cp.`product_id`
                UNION ALL
                SELECT " . self::tenantColumns('fs') . ", fsp.`product_id` AS `product_id`
                  FROM `flash_sale_product` fsp
                  JOIN `flash_sales` fs ON fs.`id` = fsp.`flash_sale_id` AND fs.`central_flash_sale_id` IS NULL";
    }

    /**
     * SQL predicate: the merged row (alias $alias, columns `source` / `source_id`) applies to the country (targets all
     * countries or that country), like HasCountries::countryScopeSql(). A null country sees all-countries rows only.
     */
    public static function countrySql(string $alias, ?int $countryId): string
    {
        $central = CentralTable::name('central_flash_sale_country');
        $one = function (string $pivot, string $key) use ($alias, $countryId): string {
            $none = "NOT EXISTS (SELECT 1 FROM {$pivot} fc WHERE fc.`{$key}` = {$alias}.`source_id`)";

            return $countryId
                ? "({$none} OR EXISTS (SELECT 1 FROM {$pivot} fc WHERE fc.`{$key}` = {$alias}.`source_id` AND fc.`country_id` = " . (int) $countryId . '))'
                : $none;
        };

        return "(({$alias}.`source` = 'central' AND " . $one($central, 'central_flash_sale_id') . ")
                 OR ({$alias}.`source` = 'tenant' AND " . $one('`flash_sale_country`', 'flash_sale_id') . '))';
    }

    /** SQL predicate: active and inside its window now (alias $alias), like FlashSale::scopeActiveWindow(). */
    public static function windowSql(string $alias): string
    {
        return "{$alias}.`active` = 1 AND ({$alias}.`start_date` IS NULL OR {$alias}.`start_date` <= CURRENT_TIMESTAMP)
                AND ({$alias}.`end_date` IS NULL OR {$alias}.`end_date` >= CURRENT_TIMESTAMP)";
    }

    /**
     * Correlated SQL expression (outer alias `products`): best active flash-sale discount % for the product in the
     * country, 0 when none. Two indexed sub-queries (central pivot by central product id, own pivot by tenant id)
     * instead of a derived table per outer row. Same value as StorefrontRepository's synced-table expression.
     */
    public static function discountExpression(?int $countryId, ?ConnectionInterface $db = null): string
    {
        $db ??= DB::connection('tenant');
        $cfs = CentralTable::name('central_flash_sales');
        $cfp = CentralTable::name('central_flash_sale_product');
        $cfc = CentralTable::name('central_flash_sale_country');
        $country = fn (string $pivot, string $key, string $a) => $countryId
            ? "(NOT EXISTS (SELECT 1 FROM {$pivot} fc WHERE fc.`{$key}` = {$a}.`id`) OR EXISTS (SELECT 1 FROM {$pivot} fc WHERE fc.`{$key}` = {$a}.`id` AND fc.`country_id` = " . (int) $countryId . '))'
            : "NOT EXISTS (SELECT 1 FROM {$pivot} fc WHERE fc.`{$key}` = {$a}.`id`)";

        $central = "SELECT MAX(cfs.`discount_percentage`) FROM {$cfp} cp
                      JOIN {$cfs} cfs ON cfs.`id` = cp.`central_flash_sale_id` AND " . self::centralLive($db) . "
                     WHERE cp.`product_id` = products.`central_product_id` AND " . self::windowSql('cfs') . ' AND ' . $country($cfc, 'central_flash_sale_id', 'cfs');
        $own = "SELECT MAX(fs.`discount_percentage`) FROM `flash_sale_product` fsp
                  JOIN `flash_sales` fs ON fs.`id` = fsp.`flash_sale_id` AND fs.`central_flash_sale_id` IS NULL
                 WHERE " . (ProductCatalog::enabled() ? ProductCatalog::pairMatchSql('fsp.`product_type`', 'fsp.`catalog_product_id`') : 'fsp.`product_id` = products.`id`') . " AND " . self::windowSql('fs') . ' AND ' . $country('`flash_sale_country`', 'flash_sale_id', 'fs');

        return "GREATEST(COALESCE(({$central}), 0), COALESCE(({$own}), 0))";
    }

    // ── Reads ───────────────────────────────────────────────────────────────────────────────────────────────────

    /**
     * Merged sales newest first, optionally only those active now for a country, with their tenant products set as
     * the `products` relation (default Product scopes apply, like `FlashSale::with('products')`).
     *
     * @param  int|string|null  $country  activeOnly: the visitor country id; list: 'all' | country id | null
     * @return EloquentCollection<int, MergedFlashSale>
     */
    public function sales(bool $activeOnly = false, int|string|null $country = null): EloquentCollection
    {
        $query = MergedFlashSale::query()->orderByDesc('created_at')->orderByDesc('source')->orderByDesc('source_id');

        if ($activeOnly) {
            $query->activeWindow()->forCountry($country === null || $country === '' ? null : (int) $country);
        } elseif ($country === 'all') {
            $query->forAllCountries();
        } elseif ($country !== null && $country !== '') {
            $query->forCountry((int) $country);
        }

        return $this->withProducts($query->get());
    }

    /** @param EloquentCollection<int, MergedFlashSale> $sales */
    public function withProducts(EloquentCollection $sales): EloquentCollection
    {
        if ($sales->isEmpty()) {
            return $sales;
        }

        $db = DB::connection('tenant');
        $pairs = $db->query()
            ->fromRaw('(' . self::membershipSql($db) . ') as m')
            ->whereIn('m.id', $sales->pluck('id')->all())
            ->orderBy('m.product_id')
            ->get(['m.id', 'm.product_id']);

        $products = Product::query()->whereIn('id', $pairs->pluck('product_id')->unique()->all())->get()->keyBy('id');

        foreach ($sales as $sale) {
            $sale->setRelation('products', new EloquentCollection(
                $pairs->where('id', $sale->id)->map(fn (object $pair) => $products->get((int) $pair->product_id))->filter()->values()->all()
            ));
        }

        return $sales;
    }

    /** One merged sale by ref string ("c:1" / "t:2"). */
    public function find(string $ref): ?MergedFlashSale
    {
        $sale = MergedFlashSale::query()->where('id', $ref)->first();

        return $sale === null ? null : $this->withProducts(new EloquentCollection([$sale]))->first();
    }

    /** Whether the tenant product takes part in a merged active (status flag only, like the panel insight) sale. */
    public function productInActiveSale(int $productId): bool
    {
        $db = DB::connection('tenant');

        return $db->query()->fromRaw('(' . self::membershipSql($db) . ') as m')
            ->where('m.product_id', $productId)
            ->where('m.active', 1)
            ->exists();
    }

    /**
     * Banner URL of a central sale computed exactly like CentralFlashSale::getBannerUrlAttribute() (the value the sync
     * stored in the copy's `banner_image`): storage path, else the central `files` row with key `banner`.
     */
    public static function centralBannerUrl(int $centralSaleId, ?string $bannerImage): ?string
    {
        if (filled($bannerImage)) {
            return asset('storage/' . $bannerImage);
        }

        $path = DB::connection('tenant')->table(CentralTable::qualified('files'))
            ->where('model_type', (new CentralFlashSale())->getMorphClass())
            ->where('model_id', $centralSaleId)
            ->where('key', 'banner')
            ->value('path');

        return $path ? asset('storage/' . $path) : null;
    }
}
