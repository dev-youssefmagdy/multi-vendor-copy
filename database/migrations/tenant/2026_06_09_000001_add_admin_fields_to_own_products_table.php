<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'sku')) {
                $table->string('sku', 100)->nullable()->after('id');
            }
            if (!Schema::hasColumn('products', 'sale_price')) {
                $table->decimal('sale_price', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'cost_price')) {
                $table->decimal('cost_price', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'min_stock')) {
                $table->integer('min_stock')->default(0);
            }
            if (!Schema::hasColumn('products', 'manage_stock')) {
                $table->boolean('manage_stock')->default(true);
            }
            if (!Schema::hasColumn('products', 'is_taxable')) {
                $table->boolean('is_taxable')->default(true);
            }
            if (!Schema::hasColumn('products', 'weight_grams')) {
                $table->integer('weight_grams')->nullable();
            }
        });

        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'title')) {
                $table->string('title')->nullable()->after('id');
            }
            if (!Schema::hasColumn('product_variants', 'sku')) {
                $table->string('sku', 100)->nullable()->after('title');
            }
            if (!Schema::hasColumn('product_variants', 'weight_grams')) {
                $table->integer('weight_grams')->nullable()->after('sku');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('products', 'sku') ? 'sku' : null,
                Schema::hasColumn('products', 'sale_price') ? 'sale_price' : null,
                Schema::hasColumn('products', 'cost_price') ? 'cost_price' : null,
                Schema::hasColumn('products', 'min_stock') ? 'min_stock' : null,
                Schema::hasColumn('products', 'manage_stock') ? 'manage_stock' : null,
                Schema::hasColumn('products', 'is_taxable') ? 'is_taxable' : null,
                Schema::hasColumn('products', 'weight_grams') ? 'weight_grams' : null,
            ]));
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('product_variants', 'title') ? 'title' : null,
                Schema::hasColumn('product_variants', 'sku') ? 'sku' : null,
                Schema::hasColumn('product_variants', 'weight_grams') ? 'weight_grams' : null,
            ]));
        });
    }
};
