<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Coupon / discount snapshot
            $table->unsignedBigInteger('discount_id')->nullable()->after('payment_details');
            $table->decimal('discount_percentage', 6, 2)->default(0)->after('discount_id');

            // Tax applied at order level
            $table->decimal('tax_percentage', 6, 2)->default(0)->after('discount_percentage');

            // Shipping
            $table->unsignedBigInteger('shipping_zone_rate_id')->nullable()->after('tax_percentage');
            $table->decimal('shipping_charge', 10, 2)->default(0)->after('shipping_zone_rate_id');

            // Revenue sharing
            $table->decimal('owner_profit', 12, 2)->default(0)->after('shipping_charge');

            // Payment gateway used for this order (tenant's gateway)
            $table->unsignedBigInteger('payment_gateway_id')->nullable()->after('owner_profit');

            // Soft FK indexes
            $table->index('discount_id');
            $table->index('payment_gateway_id');
            $table->index('shipping_zone_rate_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['discount_id']);
            $table->dropIndex(['payment_gateway_id']);
            $table->dropIndex(['shipping_zone_rate_id']);

            $table->dropColumn([
                'discount_id',
                'discount_percentage',
                'tax_percentage',
                'shipping_zone_rate_id',
                'shipping_charge',
                'owner_profit',
                'payment_gateway_id',
            ]);
        });
    }
};
