<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ensure products.default_price is DECIMAL(20,2) on every tenant database.
 * Idempotent: safe to run even where the column was already created at
 * this precision by 2026_06_10_000001_convert_product_prices_to_json.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE products MODIFY COLUMN `default_price` DECIMAL(20,2) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE products MODIFY COLUMN `default_price` DECIMAL(10,2) NOT NULL DEFAULT 0');
    }
};
