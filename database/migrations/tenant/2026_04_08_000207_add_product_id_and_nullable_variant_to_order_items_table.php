<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('order_items', 'product_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->unsignedBigInteger('product_id')->nullable()->after('order_id')->index();
            });
        }

        if (Schema::hasColumn('order_items', 'product_variant_id') && Schema::hasTable('product_variants')) {
            DB::statement('UPDATE order_items INNER JOIN product_variants ON product_variants.id = order_items.product_variant_id SET order_items.product_id = product_variants.product_id WHERE order_items.product_id IS NULL');
            DB::statement('ALTER TABLE order_items MODIFY product_variant_id BIGINT UNSIGNED NULL');
        }

        if (Schema::hasColumn('order_items', 'product_id')) {
            DB::statement('ALTER TABLE order_items MODIFY product_id BIGINT UNSIGNED NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('order_items', 'product_variant_id') && DB::table('order_items')->whereNull('product_variant_id')->count() === 0) {
            DB::statement('ALTER TABLE order_items MODIFY product_variant_id BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('order_items', 'product_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('product_id');
            });
        }
    }
};
