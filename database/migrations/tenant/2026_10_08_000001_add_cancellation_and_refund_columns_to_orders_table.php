<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Cancellation details (see CancellationReason / CancellationActor enums).
            $table->timestamp('cancelled_at')->nullable()->after('status');
            $table->string('cancellation_reason', 50)->nullable()->after('cancelled_at');
            $table->text('cancellation_note')->nullable()->after('cancellation_reason');
            $table->string('cancelled_by_type', 20)->nullable()->after('cancellation_note');
            $table->unsignedBigInteger('cancelled_by_id')->nullable()->after('cancelled_by_type');

            // Stock bookkeeping — makes StockService decrement/restore idempotent.
            $table->timestamp('stock_deducted_at')->nullable()->after('cancelled_by_id');
            $table->timestamp('stock_restored_at')->nullable()->after('stock_deducted_at');

            // Running total of completed refunds.
            $table->decimal('refunded_amount', 12, 2)->default(0)->after('stock_restored_at');
            $table->timestamp('refunded_at')->nullable()->after('refunded_amount');

            $table->index('cancelled_at');
        });

        // Paid orders already went through StockService::decrementForOrder() when their
        // payment was confirmed, so mark their stock as deducted.
        DB::table('orders')
            ->where('paid', true)
            ->whereNull('stock_deducted_at')
            ->update(['stock_deducted_at' => DB::raw('COALESCE(`updated_at`, `created_at`, CURRENT_TIMESTAMP)')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['cancelled_at']);
            $table->dropColumn([
                'cancelled_at',
                'cancellation_reason',
                'cancellation_note',
                'cancelled_by_type',
                'cancelled_by_id',
                'stock_deducted_at',
                'stock_restored_at',
                'refunded_amount',
                'refunded_at',
            ]);
        });
    }
};
