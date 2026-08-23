<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('flash_sales', function (Blueprint $table) {
            // Null = "Default / All Countries". References the central `countries` table
            // by id (no FK constraint — flash sales live in the per-tenant DB, countries
            // in the central DB).
            $table->unsignedBigInteger('country_id')->nullable()->after('banner_image');
        });
    }

    public function down(): void
    {
        Schema::table('flash_sales', function (Blueprint $table) {
            $table->dropColumn('country_id');
        });
    }
};
