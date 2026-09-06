<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')
                  ->constrained('affiliates')
                  ->cascadeOnDelete();

            // The promo code the affiliate shares (e.g. "JOHN20")
            $table->string('code', 64)->unique();

            // Discount given to the customer at registration
            $table->string('discount_type', 20)->default('percentage'); // 'percentage' or 'fixed'
            $table->decimal('discount_value', 10, 2)->default(0.00);

            // Commission the affiliate earns when this code is used
            // NULL = use the affiliate's own default commission_type + commission_value
            // Non-null = always treated as a percentage of the sale amount
            $table->decimal('commission_value', 8, 2)->nullable();

            // Optional validity window
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->decimal('minimum_spend', 10, 2)->default(0.00);

            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['affiliate_id', 'active']);
            $table->index(['code', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_coupons');
    }
};
