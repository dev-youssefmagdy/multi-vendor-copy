<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_conversions', function (Blueprint $table) {
            $table->foreignId('coupon_id')
                  ->nullable()
                  ->after('affiliate_referral_id')
                  ->constrained('central_coupons')
                  ->nullOnDelete();

            // 'referral' = came from affiliate link, 'coupon' = entered affiliate's coupon code
            $table->string('source', 20)->default('referral')->after('coupon_id');
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_conversions', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn(['coupon_id', 'source']);
        });
    }
};
