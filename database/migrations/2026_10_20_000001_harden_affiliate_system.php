<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // status gains 'rejected' and 'refunded'; a plain string is portable across drivers.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE affiliate_conversions MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        }

        Schema::table('affiliate_conversions', function (Blueprint $table) {
            $table->decimal('paid_amount', 12, 2)->default(0.00)->after('commission_amount');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
            $table->unique('payment_log_id');
        });

        // Backfill: conversions already marked paid were fully paid.
        DB::table('affiliate_conversions')->where('status', 'paid')->update(['paid_amount' => DB::raw('commission_amount')]);

        Schema::create('affiliate_payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending'); // pending, fulfilled, rejected
            $table->text('note')->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('affiliate_payout_id')->nullable()->constrained('affiliate_payouts')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['affiliate_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_payout_requests');

        Schema::table('affiliate_conversions', function (Blueprint $table) {
            $table->dropUnique(['payment_log_id']);
            $table->dropColumn(['paid_amount', 'refunded_at']);
        });
    }
};
