<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_conversions', function (Blueprint $table) {
            if (Schema::hasColumn('affiliate_conversions', 'coupon_id')) {
                try { $table->dropForeign(['coupon_id']); } catch (\Throwable) {}
                $table->dropColumn('coupon_id');
            }

            $table->foreignId('affiliate_coupon_id')
                  ->nullable()
                  ->after('affiliate_referral_id')
                  ->constrained('affiliate_coupons')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_conversions', function (Blueprint $table) {
            $table->dropForeign(['affiliate_coupon_id']);
            $table->dropColumn('affiliate_coupon_id');
            $table->foreignId('coupon_id')->nullable()->constrained('central_coupons')->nullOnDelete();
        });
    }
};
