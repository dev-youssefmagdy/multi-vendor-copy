<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storefront home indexes (WP5 of docs/HOME_PERFORMANCE_PLAN.md).
 *
 *  - product_variants(product_id, active, default_sell_price, stock): covering index for the
 *    per-product dependent subqueries of StorefrontRepository::productBaseQuery() — stock
 *    `SUM(pv.stock) ... WHERE pv.product_id = products.id` and effective price
 *    `MIN(pv.default_sell_price) ... WHERE pv.product_id = products.id AND pv.active = 1` —
 *    which run once per active product (no row lookups any more).
 *  - product_rates(product_id, stars): covering index for the `AVG(product_rates.stars)`
 *    per-product subquery (top-rated listing/count).
 *  - products(active, central_visible, created_at) and
 *    products(active, central_visible, orders_count, created_at): Product has a global
 *    `central_visible = 1` scope, so every storefront list filters on both flags and
 *    orders by created_at / orders_count.
 *
 * Every index is guarded (table + columns exist, index absent) so the migration is safe on
 * tenants with older schemas; down() only drops indexes by the names created here.
 */
return new class extends Migration
{
    private const INDEXES = [
        ['product_variants', 'pv_product_active_price_stock_index', ['product_id', 'active', 'default_sell_price', 'stock']],
        ['product_rates', 'product_rates_product_id_stars_index', ['product_id', 'stars']],
        ['products', 'products_active_visible_created_at_index', ['active', 'central_visible', 'created_at']],
        ['products', 'products_active_visible_orders_created_index', ['active', 'central_visible', 'orders_count', 'created_at']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as [$table, $name, $columns]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumns($table, $columns) || Schema::hasIndex($table, $name)) {
                continue;
            }

            Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as [$table, $name]) {
            if (Schema::hasTable($table) && Schema::hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
            }
        }
    }
};
