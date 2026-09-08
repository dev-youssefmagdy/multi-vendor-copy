<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            // Tracks which affiliate referral brought this registration
            $table->foreignId('affiliate_referral_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('affiliate_referrals')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->dropForeign(['affiliate_referral_id']);
            $table->dropColumn('affiliate_referral_id');
        });
    }
};
