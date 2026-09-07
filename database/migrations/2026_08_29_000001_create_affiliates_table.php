<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('code', 32)->unique(); // referral URL code e.g. "JOHN2024"
            $table->enum('commission_type', ['fixed', 'percentage'])->default('percentage');
            $table->decimal('commission_value', 10, 2)->default(10.00); // % or USD
            $table->decimal('balance', 12, 2)->default(0.00); // available (unpaid) balance
            $table->decimal('total_earned', 12, 2)->default(0.00); // lifetime earned
            $table->decimal('total_paid', 12, 2)->default(0.00); // lifetime paid out
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->string('paypal_email')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_iban')->nullable();
            $table->text('notes')->nullable(); // admin notes
            $table->timestamp('approved_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
