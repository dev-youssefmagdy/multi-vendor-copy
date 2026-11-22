<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan phase 5 (favorites + product_rates): with `catalog_source.products = central` a favourite / rating of a CENTRAL
 * product has no tenant `products` row. `product_id` becomes nullable and loses its FK to `products` (own rows still fill it),
 * identity lives in the phase-1 typed pair (`product_type`, `catalog_product_id`). favorites keeps its unique (customer, product)
 * and gains unique (customer, product_type, catalog_product_id) (NULLs never collide).
 * Idempotent; ratings keep their (product_id, stars) index.
 */
return new class extends Migration {
    private function fk(string $table, string $name): bool
    {
        return DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = \'FOREIGN KEY\'',
            [$table, $name],
        ) !== null;
    }

    private function index(string $table, string $name): bool
    {
        return DB::selectOne('SELECT 1 AS ok FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?', [$table, $name]) !== null;
    }

    public function up(): void
    {
        foreach (['favorites' => 'favorites_product_id_foreign', 'product_rates' => 'product_rates_product_id_foreign'] as $table => $fk) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'catalog_product_id')) {
                continue;
            }
            // The FK (ON DELETE CASCADE) stays: NULL product_id (central product) passes it; own-product delete still cascades.
            DB::statement("ALTER TABLE `{$table}` MODIFY `product_id` BIGINT UNSIGNED NULL");
        }

        if (Schema::hasTable('favorites') && Schema::hasColumn('favorites', 'catalog_product_id')) {
            if (! $this->index('favorites', 'favorites_customer_product_ref_unique')) {
                DB::statement('ALTER TABLE `favorites` ADD UNIQUE `favorites_customer_product_ref_unique` (`customer_id`, `product_type`, `catalog_product_id`)');
            }
            if (! $this->index('favorites', 'favorites_customer_id_index')) {
                DB::statement('ALTER TABLE `favorites` ADD INDEX `favorites_customer_id_index` (`customer_id`)');
            }
        }
    }

    public function down(): void
    {
        // Not reversible without losing central-only rows; left relaxed on purpose.
    }
};
