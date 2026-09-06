<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->foreignId('affiliate_referral_id')->nullable()->constrained('affiliate_referrals')->nullOnDelete();
            $table->string('tenant_id')->index();
            $table->foreignId('payment_log_id')->nullable()->constrained('payment_logs')->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->decimal('sale_amount', 12, 2)->default(0.00);  // what the tenant paid
            $table->decimal('commission_amount', 12, 2)->default(0.00); // what affiliate earns
            $table->string('commission_type', 20);  // 'fixed' or 'percentage'
            $table->decimal('commission_value', 10, 2); // the rate at time of conversion
            // pending = waiting for first paid package; approved = added to balance; paid = included in payout
            $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_conversions');
    }
};
