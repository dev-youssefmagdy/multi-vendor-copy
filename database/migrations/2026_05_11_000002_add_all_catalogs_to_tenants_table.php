<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // true = tenant receives all catalogs (no restriction); false = specific catalog_id only
            $table->boolean('all_catalogs')->default(false)->after('catalog_id');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('all_catalogs');
        });
    }
};
