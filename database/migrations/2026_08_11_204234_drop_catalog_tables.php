<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('category_catalog');
        Schema::dropIfExists('catalogs');

        if (Schema::hasColumn('packages', 'catalog_id')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->dropForeign(['catalog_id']);
                $table->dropColumn('catalog_id');
            });
        }

        if (Schema::hasColumn('packages', 'all_catalogs')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->dropColumn('all_catalogs');
            });
        }

        Schema::dropIfExists('package_catalogs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally not reversible — catalog system removed
    }
};
