<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // JSON array of central country IDs this product is prioritized for.
            // NULL or empty array = available everywhere (no country preference).
            $table->json('allowed_country_ids')->nullable()->after('central_visible');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('allowed_country_ids');
        });
    }
};
