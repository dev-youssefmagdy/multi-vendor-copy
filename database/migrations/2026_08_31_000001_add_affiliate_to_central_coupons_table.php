<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_coupons', function (Blueprint $table) {
            $table->foreignId('affiliate_id')
                  ->nullable()
                  ->after('active')
                  ->constrained('affiliates')
                  ->nullOnDelete();

            // NULL = use the affiliate's own commission_type + commission_value.
            // Numeric value is always treated as a PERCENTAGE (0-100).
            $table->decimal('affiliate_commission_value', 8, 2)
                  ->nullable()
                  ->after('affiliate_id');
        });
    }

    public function down(): void
    {
        Schema::table('central_coupons', function (Blueprint $table) {
            $table->dropForeign(['affiliate_id']);
            $table->dropColumn(['affiliate_id', 'affiliate_commission_value']);
        });
    }
};
