<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan phase 5 (orders): with `catalog_source.products = central` an order line for a CENTRAL product has no tenant
 * `products` row, so `order_items.product_id` must be nullable; identity lives in the phase-1 typed pair
 * (`product_type` + `catalog_product_id`). Existing rows are untouched (no FK on product_id, just a NOT NULL relaxation).
 * Money columns are not touched. Idempotent.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('order_items') || ! Schema::hasColumn('order_items', 'product_id')) {
            return;
        }
        DB::statement('ALTER TABLE `order_items` MODIFY `product_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        // Not reversible without losing central-only lines; left nullable on purpose.
    }
};
