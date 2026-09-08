<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add vendor-purchase tracking columns to the tenant orders table.
 *
 * vendor_cost          – product cost vendor owes central (sum of real_price × qty stored at order time).
 * vendor_gateway_id    – which central payment gateway the vendor intends to use to pay central.
 * vendor_gateway_fee   – calculated fee charged by the gateway (fixed + percentage of total due).
 * vendor_settled_at    – when vendor paid central (null = not yet settled).
 * vendor_settlement_ref – payment reference / transaction ID from the gateway used to pay central.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('vendor_cost', 12, 2)->default(0)->after('owner_profit')
                ->comment('Central product cost (real_price × qty) owed by vendor');
            $table->unsignedBigInteger('vendor_gateway_id')->nullable()->after('vendor_cost')
                ->comment('Central payment gateway selected by vendor to pay central');
            $table->decimal('vendor_gateway_fee', 10, 2)->default(0)->after('vendor_gateway_id')
                ->comment('Gateway transaction fee applied when vendor pays central');
            $table->timestamp('vendor_settled_at')->nullable()->after('vendor_gateway_fee');
            $table->string('vendor_settlement_ref', 255)->nullable()->after('vendor_settled_at');

            $table->index('vendor_gateway_id');
            $table->index('vendor_settled_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['vendor_gateway_id']);
            $table->dropIndex(['vendor_settled_at']);
            $table->dropColumn([
                'vendor_cost',
                'vendor_gateway_id',
                'vendor_gateway_fee',
                'vendor_settled_at',
                'vendor_settlement_ref',
            ]);
        });
    }
};
