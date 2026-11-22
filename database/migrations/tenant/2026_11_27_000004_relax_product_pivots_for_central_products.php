<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan phase 5 (writers): with `catalog_source.products = central` the vendor's own links to CENTRAL products
 * (own badge / own category / own flash sale x central product) have no tenant `products` row. The three pivots therefore
 * lose the NOT NULL on `product_id` (the FK with ON DELETE CASCADE stays; own products still fill it) and are identified by the typed pair:
 *  - product_badge_product: unique (badge, product, country) kept (NULL product_id never collides).
 *  - category_product / flash_sale_product: composite PK (x_id, product_id) -> surrogate `id` PK + unique (x_id, product_type,
 *    catalog_product_id). The new unique is added BEFORE the PK is dropped so the FK on x_id keeps an index.
 * Idempotent.
 */
return new class extends Migration {
    private function hasFk(string $table, string $name): bool
    {
        return DB::selectOne("SELECT 1 AS ok FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'", [$table, $name]) !== null;
    }

    private function hasIndex(string $table, string $name): bool
    {
        return DB::selectOne('SELECT 1 AS ok FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?', [$table, $name]) !== null;
    }

    public function up(): void
    {
        $this->relax('product_badge_product', 'product_badge_product_product_id_foreign');
        $this->relaxFlashSalesProduct();

        foreach ([['category_product', 'category_product_product_id_foreign', 'category_id', 'category_product_ref_unique'],
                  ['flash_sale_product', 'flash_sale_product_product_id_foreign', 'flash_sale_id', 'flash_sale_product_ref_unique']] as [$table, $fk, $owner, $unique]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'catalog_product_id')) {
                continue;
            }
            if (! $this->hasIndex($table, $unique)) {
                DB::statement("ALTER TABLE `{$table}` ADD UNIQUE `{$unique}` (`{$owner}`, `product_type`, `catalog_product_id`)");
            }
            // The FK to products(id) ON DELETE CASCADE stays: a NULL product_id (central product) passes it, and deleting an OWN
            // product still cascades its links (sync mode unchanged). product_id is part of the old PK, so the PK is swapped for a
            // surrogate in the same statement that makes the column nullable.
            if (! Schema::hasColumn($table, 'id')) {
                DB::statement("ALTER TABLE `{$table}` DROP PRIMARY KEY, ADD `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST, MODIFY `product_id` BIGINT UNSIGNED NULL");
            } else {
                DB::statement("ALTER TABLE `{$table}` MODIFY `product_id` BIGINT UNSIGNED NULL");
            }
            // Keeps the old PK semantics for tenant-id rows (sync-mode `insertOrIgnore` relies on it); NULL product_id never collides.
            if (! $this->hasIndex($table, "{$table}_owner_product_unique")) {
                DB::statement("ALTER TABLE `{$table}` ADD UNIQUE `{$table}_owner_product_unique` (`{$owner}`, `product_id`)");
            }
        }
    }

    private function relaxFlashSalesProduct(): void
    {
        // flash_sales.product_id is the legacy single-product column: it cannot hold a CENTRAL product (no tenant row).
        if (Schema::hasTable('flash_sales') && Schema::hasColumn('flash_sales', 'product_id')) {
            DB::statement('ALTER TABLE `flash_sales` MODIFY `product_id` BIGINT UNSIGNED NULL');
        }
    }

    private function relax(string $table, string $fk): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'catalog_product_id')) {
            return;
        }
        DB::statement("ALTER TABLE `{$table}` MODIFY `product_id` BIGINT UNSIGNED NULL");
    }

    public function down(): void
    {
        // Not reversible without losing central-only rows; left relaxed on purpose.
    }
};
