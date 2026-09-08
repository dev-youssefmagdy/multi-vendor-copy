<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // Null = "Default / All Countries". References the central `countries` table
            // by id (no FK constraint — coupons live in the per-tenant DB, countries
            // in the central DB).
            $table->unsignedBigInteger('country_id')->nullable()->after('minimum_spend');
        });

        DB::table('coupons')->whereNotNull('allowed_country_ids')->orderBy('id')->each(function ($coupon) {
            $ids = json_decode((string) $coupon->allowed_country_ids, true) ?: [];

            if (count($ids) === 1) {
                DB::table('coupons')->where('id', $coupon->id)->update(['country_id' => $ids[0]]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('country_id');
        });
    }
};
