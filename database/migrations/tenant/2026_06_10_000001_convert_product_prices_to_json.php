<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Convert product pricing columns from scalar decimals to per-country JSON maps.
 *
 * products.price          DECIMAL(20,2) → JSON   {country_id: sell_price, "default": base_price}
 * products.default_price  NEW DECIMAL(20,2)       base sell price (no shipping) — used for SQL ORDER BY / WHERE
 *
 * product_variants.sell_price          DECIMAL(20,2) → JSON   {country_id: sell_price, "default": base_price}
 * product_variants.default_sell_price  NEW DECIMAL(20,2)       base sell price — used for SQL ORDER BY / WHERE
 */
return new class extends Migration {
    public function up(): void
    {
        // ── products ────────────────────────────────────────────────────────

        // 1. Capture current decimal values in a backup column.
        DB::statement('ALTER TABLE products ADD COLUMN `_price_dec` DECIMAL(20,2) NOT NULL DEFAULT 0');
        DB::statement('UPDATE products SET `_price_dec` = COALESCE(`price`, 0)');

        // 2. Drop the decimal column and re-add as JSON.
        DB::statement('ALTER TABLE products DROP COLUMN `price`');
        DB::statement('ALTER TABLE products ADD COLUMN `price` JSON NULL');

        // 3. Seed JSON: {"default": <old_value>} preserving existing vendor prices.
        DB::statement("UPDATE products SET `price` = JSON_OBJECT('default', `_price_dec`)");

        // 4. Add the scalar companion column for SQL sorting/filtering.
        DB::statement('ALTER TABLE products ADD COLUMN `default_price` DECIMAL(20,2) NOT NULL DEFAULT 0');
        DB::statement('UPDATE products SET `default_price` = `_price_dec`');

        // 5. Remove backup column.
        DB::statement('ALTER TABLE products DROP COLUMN `_price_dec`');

        // ── product_variants ─────────────────────────────────────────────────

        // 1. Capture current decimal values in a backup column.
        DB::statement('ALTER TABLE product_variants ADD COLUMN `_sell_dec` DECIMAL(20,2) NOT NULL DEFAULT 0');
        DB::statement('UPDATE product_variants SET `_sell_dec` = COALESCE(`sell_price`, 0)');

        // 2. Drop the decimal column and re-add as JSON.
        DB::statement('ALTER TABLE product_variants DROP COLUMN `sell_price`');
        DB::statement('ALTER TABLE product_variants ADD COLUMN `sell_price` JSON NULL');

        // 3. Seed JSON: {"default": <old_value>}
        DB::statement("UPDATE product_variants SET `sell_price` = JSON_OBJECT('default', `_sell_dec`)");

        // 4. Add scalar companion column.
        DB::statement('ALTER TABLE product_variants ADD COLUMN `default_sell_price` DECIMAL(20,2) NOT NULL DEFAULT 0');
        DB::statement('UPDATE product_variants SET `default_sell_price` = `_sell_dec`');

        // 5. Remove backup column.
        DB::statement('ALTER TABLE product_variants DROP COLUMN `_sell_dec`');
    }

    public function down(): void
    {
        // ── product_variants ─────────────────────────────────────────────────
        DB::statement('ALTER TABLE product_variants ADD COLUMN `_sell_json` JSON NULL');
        DB::statement('UPDATE product_variants SET `_sell_json` = `sell_price`');
        DB::statement('ALTER TABLE product_variants DROP COLUMN `sell_price`');
        DB::statement('ALTER TABLE product_variants ADD COLUMN `sell_price` DECIMAL(20,2) NOT NULL DEFAULT 0');
        DB::statement("UPDATE product_variants SET `sell_price` = COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(`_sell_json`, '$.\"default\"')) AS DECIMAL(20,2)), `default_sell_price`, 0)");
        DB::statement('ALTER TABLE product_variants DROP COLUMN `_sell_json`');
        DB::statement('ALTER TABLE product_variants DROP COLUMN `default_sell_price`');

        // ── products ────────────────────────────────────────────────────────
        DB::statement('ALTER TABLE products ADD COLUMN `_price_json` JSON NULL');
        DB::statement('UPDATE products SET `_price_json` = `price`');
        DB::statement('ALTER TABLE products DROP COLUMN `price`');
        DB::statement('ALTER TABLE products ADD COLUMN `price` DECIMAL(20,2) NOT NULL DEFAULT 0');
        DB::statement("UPDATE products SET `price` = COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(`_price_json`, '$.\"default\"')) AS DECIMAL(20,2)), `default_price`, 0)");
        DB::statement('ALTER TABLE products DROP COLUMN `_price_json`');
        DB::statement('ALTER TABLE products DROP COLUMN `default_price`');
    }
};
