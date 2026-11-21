<?php

use App\Support\Catalog\CatalogRefColumns;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan phase 1 (§5, §5a, invariant 3): typed references BESIDE the existing tenant id columns.
 *
 * For every tenant table that references a synced catalog entity (= CatalogRefColumns::TENANT_TABLES today), and for each
 * referenced side: `<x>_type` VARCHAR(10) NULL ('central'|'tenant') + `catalog_<x>_id` BIGINT UNSIGNED NULL
 * (central id when central, tenant id when tenant) + index (`<x>_type`, `catalog_<x>_id`).
 *
 *   order_items            product_*, variant_*
 *   favorites              product_*
 *   product_rates          product_*
 *   flash_sales            product_*            (legacy single-product column)
 *   category_product       product_*, category_*
 *   product_badge_product  product_*, badge_*
 *   product_label_product  product_*, label_*
 *   flash_sale_product     product_*, flash_sale_*
 *
 * Columns are nullable and appended (INSTANT on MySQL 8.0.29+; the index builds are INPLACE, DML allowed). No data
 * is written here: `php artisan catalog:backfill-refs` fills existing rows, the dual-write fills new ones. Readers
 * keep using the existing id columns until each section's cutover. `mobile_carts.items` (JSON) needs no column.
 * Idempotent (each column / index guarded), so it can be re-run after a partial failure.
 */
return new class extends Migration {
    /** Frozen copy of CatalogRefColumns::TENANT_TABLES / PAIRS at the time of this migration: table => entities. */
    private const TABLES = [
        'order_items' => ['product', 'variant'],
        'favorites' => ['product'],
        'product_rates' => ['product'],
        'flash_sales' => ['product'],
        'category_product' => ['product', 'category'],
        'product_badge_product' => ['product', 'badge'],
        'product_label_product' => ['product', 'label'],
        'flash_sale_product' => ['product', 'flash_sale'],
    ];

    private const PAIRS = [
        'product' => ['product_type', 'catalog_product_id'],
        'variant' => ['variant_type', 'catalog_variant_id'],
        'category' => ['category_type', 'catalog_category_id'],
        'badge' => ['badge_type', 'catalog_badge_id'],
        'label' => ['label_type', 'catalog_label_id'],
        'flash_sale' => ['flash_sale_type', 'catalog_flash_sale_id'],
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table => $entities) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($entities as $entity) {
                [$type, $id] = self::PAIRS[$entity];

                if (! Schema::hasColumn($table, $type)) {
                    Schema::table($table, fn (Blueprint $t) => $t->string($type, 10)->nullable());
                }
                if (! Schema::hasColumn($table, $id)) {
                    Schema::table($table, fn (Blueprint $t) => $t->unsignedBigInteger($id)->nullable());
                }

                $index = $this->indexName($table, $entity);
                if (! Schema::hasIndex($table, $index)) {
                    Schema::table($table, fn (Blueprint $t) => $t->index([$type, $id], $index));
                }
            }
        }

        CatalogRefColumns::flush();
    }

    public function down(): void
    {
        foreach (self::TABLES as $table => $entities) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($entities as $entity) {
                [$type, $id] = self::PAIRS[$entity];

                $index = $this->indexName($table, $entity);
                if (Schema::hasIndex($table, $index)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($index));
                }

                $drop = array_values(array_filter([$type, $id], fn ($c) => Schema::hasColumn($table, $c)));
                if ($drop !== []) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropColumn($drop));
                }
            }
        }

        CatalogRefColumns::flush();
    }

    /** e.g. order_items_product_ref_index, flash_sale_product_flash_sale_ref_index (≤ 64 chars). */
    private function indexName(string $table, string $entity): string
    {
        return "{$table}_{$entity}_ref_index";
    }
};
