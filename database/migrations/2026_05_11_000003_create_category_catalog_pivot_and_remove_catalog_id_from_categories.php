<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1 ─ Create pivot table
        Schema::create('category_catalog', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('catalog_id')->constrained('catalogs')->cascadeOnDelete();
            $table->unique(['category_id', 'catalog_id']);
        });

        // 2 ─ Migrate existing single catalog_id assignments into the pivot
        DB::statement('
            INSERT IGNORE INTO category_catalog (category_id, catalog_id)
            SELECT id, catalog_id
            FROM categories
            WHERE catalog_id IS NOT NULL
        ');

        // 3 ─ Drop the now-redundant catalog_id column from categories
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('catalog_id');
        });
    }

    public function down(): void
    {
        // Restore catalog_id column
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('catalog_id')->nullable()->after('id')->constrained('catalogs')->nullOnDelete();
        });

        // Re-populate catalog_id from first pivot entry per category
        DB::statement('
            UPDATE categories c
            JOIN (
                SELECT category_id, MIN(catalog_id) AS catalog_id
                FROM category_catalog
                GROUP BY category_id
            ) cc ON cc.category_id = c.id
            SET c.catalog_id = cc.catalog_id
        ');

        Schema::dropIfExists('category_catalog');
    }
};
