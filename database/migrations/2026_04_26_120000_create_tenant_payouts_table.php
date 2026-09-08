<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tenant_payouts: central -> tenant payments (record-only).
 *
 * Each row is created by central admin when paying a tenant the net amount
 * collected through central-owned gateways. Stores invoice number, bank or
 * gateway name, transactional reference and an optional snapshot of the orders
 * settled by the payout. Used to drive the "central owes tenant" ledger.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('invoice_number')->unique();
            $table->string('tenant_name')->nullable();
            $table->string('tenant_email')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 8)->default('USD');
            $table->string('method', 32)->default('bank'); // bank | gateway
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('gateway_name')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->text('note')->nullable();
            $table->json('orders_snapshot')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_payouts');
    }
};
