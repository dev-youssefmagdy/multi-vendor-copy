<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add all_catalogs flag to packages (default true = no catalog restriction)
        Schema::table('packages', function (Blueprint $table) {
            $table->boolean('all_catalogs')->default(true)->after('features');
        });

        // Pivot table for specific catalog restrictions per package
        Schema::create('package_catalogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->foreignId('catalog_id')->constrained('catalogs')->cascadeOnDelete();
            $table->unique(['package_id', 'catalog_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_catalogs');

        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('all_catalogs');
        });
    }
};
