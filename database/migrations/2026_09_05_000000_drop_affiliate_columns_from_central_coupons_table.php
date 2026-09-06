<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_coupons', function (Blueprint $table) {
            if (Schema::hasColumn('central_coupons', 'affiliate_commission_value')) {
                $table->dropColumn('affiliate_commission_value');
            }
            if (Schema::hasColumn('central_coupons', 'affiliate_id')) {
                try { $table->dropForeign(['affiliate_id']); } catch (\Throwable) {}
                $table->dropColumn('affiliate_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('central_coupons', function (Blueprint $table) {
            $table->foreignId('affiliate_id')->nullable()->constrained('affiliates')->nullOnDelete();
            $table->decimal('affiliate_commission_value', 8, 2)->nullable()->after('affiliate_id');
        });
    }
};
