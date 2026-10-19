<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-order settlement ledger (docs/ORDER_FORMULAS.md section 3):
 *   vendor_settlement  vendor   -> platform  (reduces vendor_own_platform)
 *   platform_payout    platform -> vendor    (reduces platform_own_vendor)
 */
return new class extends Migration {
    public const TYPE_VENDOR_SETTLEMENT = 'vendor_settlement';
    public const TYPE_PLATFORM_PAYOUT = 'platform_payout';

    public function up(): void
    {
        if (! Schema::hasTable('order_transactions')) {
            Schema::create('order_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->string('type', 30);
                $table->decimal('amount', 12, 2)->default(0);
                $table->decimal('gateway_fee', 10, 2)->default(0);
                $table->string('reference')->nullable();
                $table->unsignedBigInteger('gateway_id')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'type']);
            });
        }

        // Backfill from the legacy flags so balances stay identical.
        $orders = DB::table('orders')->where(function ($q) {
            $q->whereNotNull('vendor_settled_at')->orWhere('payout_released', true);
        })->get();

        foreach ($orders as $order) {
            if ($order->vendor_settled_at !== null && $order->collected_by === 'vendor'
                && ! DB::table('order_transactions')->where('order_id', $order->id)->where('type', self::TYPE_VENDOR_SETTLEMENT)->exists()) {
                DB::table('order_transactions')->insert([
                    'order_id' => $order->id,
                    'type' => self::TYPE_VENDOR_SETTLEMENT,
                    'amount' => max(0, (float) $order->platform_profit),
                    'gateway_fee' => (float) $order->vendor_gateway_fee,
                    'reference' => $order->vendor_settlement_ref,
                    'gateway_id' => $order->vendor_gateway_id,
                    'created_at' => $order->vendor_settled_at,
                    'updated_at' => $order->vendor_settled_at,
                ]);
            }

            if ($order->payout_released && $order->collected_by === 'platform'
                && ! DB::table('order_transactions')->where('order_id', $order->id)->where('type', self::TYPE_PLATFORM_PAYOUT)->exists()) {
                DB::table('order_transactions')->insert([
                    'order_id' => $order->id,
                    'type' => self::TYPE_PLATFORM_PAYOUT,
                    'amount' => max(0, (float) $order->vendor_profit),
                    'created_at' => $order->updated_at,
                    'updated_at' => $order->updated_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_transactions');
    }
};
