<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add gateway transaction fee fields to the central payment_gateways table.
 *
 * transaction_fee_percentage – e.g. 3.75 means the gateway charges 3.75% of the transaction amount.
 * transaction_fee_fixed      – e.g. 0.50 means the gateway charges a flat $0.50 per transaction.
 *
 * These fees are applied on top of the vendor's cost-of-goods + shipping when calculating the
 * total the vendor owes the central platform for each fulfilled order.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->decimal('transaction_fee_percentage', 7, 4)->default(0)->after('credentials')
                ->comment('Gateway percentage fee, e.g. 3.75 = 3.75%');
            $table->decimal('transaction_fee_fixed', 10, 2)->default(0)->after('transaction_fee_percentage')
                ->comment('Gateway flat fee per transaction, e.g. 0.50 = $0.50');
        });
    }

    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropColumn(['transaction_fee_percentage', 'transaction_fee_fixed']);
        });
    }
};
