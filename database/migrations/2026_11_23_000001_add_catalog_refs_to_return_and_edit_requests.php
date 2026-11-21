<?php

use App\Support\Catalog\CatalogRefColumns;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan phase 1 (§4.2a, §5, §5a.1): CENTRAL tables that store TENANT product/variant ids get typed refs beside
 * them (the existing id columns stay; readers keep using them until the products cutover):
 *
 *   return_requests        product_type + catalog_product_id                       (product_id)
 *                          variant_type + catalog_variant_id                       (product_variant_id)
 *                          replacement_variant_type + catalog_replacement_variant_id (replacement_product_variant_id)
 *   product_edit_requests  product_type + catalog_product_id, central_product_id   (product_id)
 *
 * Nullable, appended, indexed (tenant_id, type, id). Filled per tenant by `catalog:backfill-refs` and on save by
 * HasCatalogRefs. Idempotent.
 */
return new class extends Migration {
    private const PAIRS = [
        'return_requests' => [
            ['product_type', 'catalog_product_id', 'return_requests_product_ref_index'],
            ['variant_type', 'catalog_variant_id', 'return_requests_variant_ref_index'],
            ['replacement_variant_type', 'catalog_replacement_variant_id', 'return_requests_replacement_ref_index'],
        ],
        'product_edit_requests' => [
            ['product_type', 'catalog_product_id', 'product_edit_requests_product_ref_index'],
        ],
    ];

    public function up(): void
    {
        foreach (self::PAIRS as $table => $pairs) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($pairs as [$type, $id, $index]) {
                if (! Schema::hasColumn($table, $type)) {
                    Schema::table($table, fn (Blueprint $t) => $t->string($type, 10)->nullable());
                }
                if (! Schema::hasColumn($table, $id)) {
                    Schema::table($table, fn (Blueprint $t) => $t->unsignedBigInteger($id)->nullable());
                }
                if (! Schema::hasIndex($table, $index)) {
                    Schema::table($table, fn (Blueprint $t) => $t->index(['tenant_id', $type, $id], $index));
                }
            }
        }

        if (Schema::hasTable('product_edit_requests') && ! Schema::hasColumn('product_edit_requests', 'central_product_id')) {
            Schema::table('product_edit_requests', function (Blueprint $t) {
                $t->unsignedBigInteger('central_product_id')->nullable();
                $t->index('central_product_id', 'product_edit_requests_central_product_id_index');
            });
        }

        CatalogRefColumns::flush();
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_edit_requests', 'central_product_id')) {
            Schema::table('product_edit_requests', function (Blueprint $t) {
                $t->dropIndex('product_edit_requests_central_product_id_index');
                $t->dropColumn('central_product_id');
            });
        }

        foreach (self::PAIRS as $table => $pairs) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($pairs as [$type, $id, $index]) {
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
};
