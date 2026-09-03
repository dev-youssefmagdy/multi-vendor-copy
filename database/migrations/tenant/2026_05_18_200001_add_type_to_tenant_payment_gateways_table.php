<?php

use App\Enums\PaymentGatewayType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add `type` enum column to the tenant payment_gateways table.
 *
 * - Existing rows are assigned the 'orders' type (backward-compatible default).
 * - The unique constraint is changed from (code) to (code, type).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->string('type')
                ->default(PaymentGatewayType::Orders->value)
                ->after('code')
                ->comment('orders = storefront customer payments | vendor_payments = vendor-to-central payments');
        });

        DB::table('payment_gateways')
            ->whereNull('type')
            ->orWhere('type', '')
            ->update(['type' => PaymentGatewayType::Orders->value]);

        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['code', 'type'], 'payment_gateways_code_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropUnique('payment_gateways_code_type_unique');
        });

        DB::table('payment_gateways')
            ->where('type', PaymentGatewayType::VendorPayments->value)
            ->delete();

        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->unique('code');
            $table->dropColumn('type');
        });
    }
};
