<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('central_coupons', function (Blueprint $table) {
            $table->unsignedBigInteger('country_id')->nullable()->after('active');
            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
        });

        if (Schema::hasTable('central_coupon_country')) {
            $singleCountryByCoupon = DB::table('central_coupon_country')
                ->select('central_coupon_id', 'country_id')
                ->get()
                ->groupBy('central_coupon_id')
                ->filter(fn($rows) => $rows->count() === 1)
                ->map(fn($rows) => $rows->first()->country_id);

            foreach ($singleCountryByCoupon as $couponId => $countryId) {
                DB::table('central_coupons')->where('id', $couponId)->update(['country_id' => $countryId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('central_coupons', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropColumn('country_id');
        });
    }
};
