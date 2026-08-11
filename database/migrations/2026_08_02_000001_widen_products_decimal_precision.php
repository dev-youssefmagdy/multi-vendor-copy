<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Widen decimal precision on the central products table from (10,x) to (20,x)
 * to match the tenant products.default_price precision and avoid overflow
 * on large aggregated prices.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE products MODIFY COLUMN `base_price` DECIMAL(20,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE products MODIFY COLUMN `sale_price` DECIMAL(20,2) NULL');
        DB::statement('ALTER TABLE products MODIFY COLUMN `cost_price` DECIMAL(20,2) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE products MODIFY COLUMN `base_price` DECIMAL(10,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE products MODIFY COLUMN `sale_price` DECIMAL(10,2) NULL');
        DB::statement('ALTER TABLE products MODIFY COLUMN `cost_price` DECIMAL(10,2) NULL');
    }
};
