<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // Denormalized JSON array of central country IDs.
            // NULL / empty = available in all countries (no restriction).
            // Populated by TenantCatalogSyncService::syncCoupons() from the
            // central_coupon_country pivot.
            $table->json('allowed_country_ids')->nullable()->after('minimum_spend');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('allowed_country_ids');
        });
    }
};
