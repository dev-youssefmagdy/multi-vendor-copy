<?php

namespace App\Support\Catalog;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Dedup plan phase 1 (§5, §9 invariant 3): the typed reference pairs `<x>_type` ('central'|'tenant') +
 * `catalog_<x>_id` (central id when central, tenant id when tenant) that sit BESIDE the existing tenant id columns.
 *
 * Single list of which table/column gets which pair (used by the migrations' tests, the dual-write, the backfill
 * and `catalog:sync-check`), plus a cached "do the columns exist yet?" check so code deployed before the
 * migrations run never writes a column that is not there.
 */
final class CatalogRefColumns
{
    /** entity => [tenant table, link column to central] (CatalogRefResolver reads these). */
    public const ENTITIES = [
        'product' => ['products', 'central_product_id'],
        'variant' => ['product_variants', 'central_product_variant_id'],
        'category' => ['categories', 'central_category_id'],
        'badge' => ['product_badges', 'central_product_badge_id'],
        'label' => ['product_labels', 'central_product_label_id'],
        'flash_sale' => ['flash_sales', 'central_flash_sale_id'],
    ];

    /** entity => default [type column, catalog id column]. */
    public const PAIRS = [
        'product' => ['product_type', 'catalog_product_id'],
        'variant' => ['variant_type', 'catalog_variant_id'],
        'category' => ['category_type', 'catalog_category_id'],
        'badge' => ['badge_type', 'catalog_badge_id'],
        'label' => ['label_type', 'catalog_label_id'],
        'flash_sale' => ['flash_sale_type', 'catalog_flash_sale_id'],
    ];

    /**
     * TENANT tables: table => [source id column => entity]. Pivots carry a pair for both sides.
     * `mobile_carts.items` (JSON) is handled separately (keys per item, see CatalogRefWriter::enrichCartItems()).
     */
    public const TENANT_TABLES = [
        'order_items' => ['product_id' => 'product', 'product_variant_id' => 'variant'],
        'favorites' => ['product_id' => 'product'],
        'product_rates' => ['product_id' => 'product'],
        'flash_sales' => ['product_id' => 'product'],
        'category_product' => ['product_id' => 'product', 'category_id' => 'category'],
        'product_badge_product' => ['product_id' => 'product', 'product_badge_id' => 'badge'],
        'product_label_product' => ['product_id' => 'product', 'product_label_id' => 'label'],
        'flash_sale_product' => ['product_id' => 'product', 'flash_sale_id' => 'flash_sale'],
    ];

    /** CENTRAL tables holding tenant ids (keyed by `tenant_id`): table => [source column => [entity, type col, id col]]. */
    public const CENTRAL_TABLES = [
        'return_requests' => [
            'product_id' => ['product', 'product_type', 'catalog_product_id'],
            'product_variant_id' => ['variant', 'variant_type', 'catalog_variant_id'],
            'replacement_product_variant_id' => ['variant', 'replacement_variant_type', 'catalog_replacement_variant_id'],
        ],
        'product_edit_requests' => [
            'product_id' => ['product', 'product_type', 'catalog_product_id'],
        ],
    ];

    /** Keys added to every `mobile_carts.items` entry. */
    public const CART_KEYS = ['product_type', 'catalog_product_id', 'variant_type', 'catalog_variant_id'];

    /** @var array<string, array{0: array<string, true>, 1: int}> connection|schema|table => [columns, checked at] */
    private static array $columns = [];

    /** @var array<string, true> app-cache keys written by this process (forgotten by flush()) */
    private static array $cacheKeys = [];

    /** App-cache TTL of a column list (seconds). Lists are also re-read when an expected column is missing (60 s). */
    private const CACHE_TTL = 3600;

    /**
     * [source column => [entity, type column, catalog id column]] for a tenant or central table.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function specFor(string $table): array
    {
        if (isset(self::CENTRAL_TABLES[$table])) {
            return self::CENTRAL_TABLES[$table];
        }

        $spec = [];
        foreach (self::TENANT_TABLES[$table] ?? [] as $column => $entity) {
            $spec[$column] = [$entity, ...self::PAIRS[$entity]];
        }

        return $spec;
    }

    /** Whether every typed pair of $table exists on that connection/schema (all are added by one migration). */
    public static function ready(ConnectionInterface $db, string $table, ?string $schema = null): bool
    {
        $spec = self::specFor($table);
        if ($spec === []) {
            return false;
        }

        foreach ($spec as [, $type, $id]) {
            if (! self::has($db, $table, $type, $schema) || ! self::has($db, $table, $id, $schema)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Cached column check per connection + schema + table (one small information_schema query per table). A "yes"
     * is cached for the process; a "no" is re-checked every 60 s, so long-running queue workers pick up a
     * `tenants:migrate` by themselves (same idea as Phase 0).
     */
    public static function has(ConnectionInterface $db, string $table, string $column, ?string $schema = null): bool
    {
        if ($db->getDriverName() !== 'mysql') {
            return $schema === null && Schema::connection($db->getName())->hasColumn($table, $column);
        }

        return isset(self::columns($db, $table, $schema, $column)[$column]);
    }

    public static function hasTable(ConnectionInterface $db, string $table, ?string $schema = null): bool
    {
        if ($db->getDriverName() !== 'mysql') {
            return $schema === null && Schema::connection($db->getName())->hasTable($table);
        }

        return self::columns($db, $table, $schema) !== [];
    }

    /**
     * @return array<string, true> columns of $table. Cached per process AND in the app cache (one information_schema
     * query per table per hour instead of per request, QA D-P1-1); re-read when $expect is missing and the list is
     * >= 60 s old, so a `tenants:migrate` is picked up within a minute by web requests and long-running workers alike.
     */
    private static function columns(ConnectionInterface $db, string $table, ?string $schema, ?string $expect = null): array
    {
        $schema ??= $db->getDatabaseName();
        $key = $db->getName() . '|' . $schema . '|' . $table;
        $cacheKey = 'catalog-ref-columns:' . md5($key);

        if (! isset(self::$columns[$key])) {
            $cached = self::cacheGet($cacheKey);
            if (is_array($cached) && isset($cached[0], $cached[1]) && is_array($cached[0])) {
                self::$columns[$key] = [$cached[0], (int) $cached[1]];
            }
        }
        [$columns, $checkedAt] = self::$columns[$key] ?? [null, 0];

        $stale = $columns === null
            || (($expect === null ? $columns === [] : ! isset($columns[$expect])) && time() - $checkedAt >= 60);

        if ($stale) {
            $columns = [];
            foreach ($db->select(
                'SELECT COLUMN_NAME AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                [$schema, $table],
            ) as $row) {
                $columns[$row->c] = true;
            }
            self::$columns[$key] = [$columns, time()];
            self::cachePut($cacheKey, [$columns, time()]);
        }

        return $columns;
    }

    private static function cacheGet(string $key): mixed
    {
        try {
            return Cache::get($key);
        } catch (Throwable) {
            return null;
        }
    }

    private static function cachePut(string $key, array $value): void
    {
        try {
            Cache::put($key, $value, self::CACHE_TTL);
            self::$cacheKeys[$key] = true;
        } catch (Throwable) {
            // no app cache: per-process caching only
        }
    }

    /** Tests / after migrations in-process: forget the cached column lists. */
    public static function flush(): void
    {
        self::$columns = [];
        foreach (array_keys(self::$cacheKeys) as $key) {
            try {
                Cache::forget($key);
            } catch (Throwable) {
            }
        }
        self::$cacheKeys = [];
    }
}
