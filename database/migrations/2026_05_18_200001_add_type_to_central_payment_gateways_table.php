<?php

use App\Enums\PaymentGatewayType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add `type` enum column to the central payment_gateways table.
 *
 * - Existing rows are assigned the 'orders' type (backward-compatible default).
 * - The unique constraint is changed from (code) to (code, type) so that a
 *   gateway can have one entry for orders and one for vendor_payments.
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

        // Seed existing rows as 'orders' type
        DB::table('payment_gateways')
            ->whereNull('type')
            ->orWhere('type', '')
            ->update(['type' => PaymentGatewayType::Orders->value]);

        // Drop the old unique constraint on code alone, add composite unique
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

        // Keep only 'orders' rows before restoring the simple code unique index
        DB::table('payment_gateways')
            ->where('type', PaymentGatewayType::VendorPayments->value)
            ->delete();

        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->unique('code');
            $table->dropColumn('type');
        });
    }
};
