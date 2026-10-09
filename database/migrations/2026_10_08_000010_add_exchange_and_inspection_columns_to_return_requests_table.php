<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->string('type', 20)->default('return')->after('order_number');
            $table->unsignedBigInteger('order_item_id')->nullable()->after('customer_id');
            $table->unsignedInteger('quantity')->default(1)->after('product_variant_id');
            $table->string('return_method', 30)->nullable()->after('quantity');
            $table->text('customer_note')->nullable()->after('description');

            // Inspection
            $table->string('inspection_result', 20)->nullable()->after('refund_amount');
            $table->text('inspection_notes')->nullable()->after('inspection_result');
            $table->timestamp('received_at')->nullable()->after('inspection_notes');
            $table->timestamp('inspected_at')->nullable()->after('received_at');
            $table->timestamp('restocked_at')->nullable()->after('inspected_at');

            // Exchange
            $table->unsignedBigInteger('replacement_product_variant_id')->nullable()->after('restocked_at');
            $table->unsignedInteger('replacement_quantity')->nullable()->after('replacement_product_variant_id');
            $table->timestamp('replacement_reserved_at')->nullable()->after('replacement_quantity');
            $table->string('exchange_tracking_number')->nullable()->after('replacement_reserved_at');
            $table->timestamp('exchange_shipped_at')->nullable()->after('exchange_tracking_number');
            $table->timestamp('exchange_completed_at')->nullable()->after('exchange_shipped_at');

            // Customer withdrawal
            $table->timestamp('cancelled_at')->nullable()->after('exchange_completed_at');

            // Reviewer can be a vendor (tenant admin) as well as a platform admin.
            $table->string('reviewed_by_type', 20)->nullable()->after('reviewed_by_admin_id');
            $table->unsignedBigInteger('reviewed_by_id')->nullable()->after('reviewed_by_type');

            $table->index(['tenant_id', 'order_number', 'order_item_id'], 'return_requests_order_item_index');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->dropIndex('return_requests_order_item_index');
            $table->dropIndex(['type']);
            $table->dropColumn([
                'type',
                'order_item_id',
                'quantity',
                'return_method',
                'customer_note',
                'inspection_result',
                'inspection_notes',
                'received_at',
                'inspected_at',
                'restocked_at',
                'replacement_product_variant_id',
                'replacement_quantity',
                'replacement_reserved_at',
                'exchange_tracking_number',
                'exchange_shipped_at',
                'exchange_completed_at',
                'cancelled_at',
                'reviewed_by_type',
                'reviewed_by_id',
            ]);
        });
    }
};
