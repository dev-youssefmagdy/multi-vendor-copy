<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Order refactor (docs/ORDER_FORMULAS.md):
 *  - order_items gets the full cost/profit/fixed/discount/tax/grand_total breakdown (in a fixed column order);
 *  - orders drops owner_profit + vendor_cost and gains vendor_profit, platform_profit, coupon_discount,
 *    coupon_source and collected_by;
 *  - legacy rows are backfilled so historical money does not change.
 */
return new class extends Migration {
    public function up(): void
    {
        $this->upgradeOrderItems();
        $this->upgradeOrders();
        $this->backfill();
        $this->orderItemColumns();

        Schema::table('orders', function ($table) {
            foreach (['owner_profit', 'vendor_cost'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function ($table) {
            if (! Schema::hasColumn('orders', 'owner_profit')) {
                $table->decimal('owner_profit', 12, 2)->default(0)->after('shipping_charge');
            }
            if (! Schema::hasColumn('orders', 'vendor_cost')) {
                $table->decimal('vendor_cost', 12, 2)->default(0)->after('owner_profit');
            }
        });

        DB::table('orders')->update([
            'owner_profit' => DB::raw('platform_profit'),
            'vendor_cost' => DB::raw('platform_profit'),
        ]);

        Schema::table('orders', function ($table) {
            $table->dropColumn(['vendor_profit', 'platform_profit', 'coupon_discount', 'coupon_source', 'collected_by']);
        });

        Schema::table('order_items', function ($table) {
            $table->dropColumn(['profit_amount', 'discount_source', 'grand_total']);
        });
    }

    private function upgradeOrderItems(): void
    {
        Schema::table('order_items', function ($table) {
            if (! Schema::hasColumn('order_items', 'cost')) {
                $table->decimal('cost', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('order_items', 'profit_amount')) {
                $table->decimal('profit_amount', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('order_items', 'fixed_shipping')) {
                $table->decimal('fixed_shipping', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('order_items', 'discount_source')) {
                $table->string('discount_source', 10)->nullable();
            }
            if (! Schema::hasColumn('order_items', 'grand_total')) {
                $table->decimal('grand_total', 12, 2)->default(0);
            }
            foreach (['tax' => 10, 'shipping_fee' => 10] as $name => $p) {
                if (! Schema::hasColumn('order_items', $name)) {
                    $table->decimal($name, $p, 2)->default(0);
                }
            }
            if (! Schema::hasColumn('order_items', 'weight')) {
                $table->decimal('weight', 10, 3)->default(0);
            }
        });
    }

    private function upgradeOrders(): void
    {
        Schema::table('orders', function ($table) {
            if (! Schema::hasColumn('orders', 'platform_profit')) {
                $table->decimal('platform_profit', 12, 2)->default(0)->after('shipping_charge');
            }
            if (! Schema::hasColumn('orders', 'vendor_profit')) {
                $table->decimal('vendor_profit', 12, 2)->default(0)->after('platform_profit');
            }
            if (! Schema::hasColumn('orders', 'coupon_discount')) {
                $table->decimal('coupon_discount', 12, 2)->default(0)->after('discount_percentage');
            }
            if (! Schema::hasColumn('orders', 'coupon_source')) {
                $table->string('coupon_source', 10)->nullable()->after('coupon_discount');
            }
            if (! Schema::hasColumn('orders', 'collected_by')) {
                $table->string('collected_by', 10)->default('platform')->after('payment_gateway_id');
            }
        });
    }

    /** Re-express legacy rows in the new columns, keeping historical totals identical. */
    private function backfill(): void
    {
        if (! Schema::hasColumn('orders', 'owner_profit')) {
            return; // already migrated / fresh install
        }

        $centralFlashProducts = Schema::hasTable('flash_sale_product')
            ? DB::table('flash_sale_product')
                ->join('flash_sales', 'flash_sales.id', '=', 'flash_sale_product.flash_sale_id')
                ->whereNotNull('flash_sales.central_flash_sale_id')
                ->pluck('flash_sale_product.product_id')->flip()->all()
            : [];

        $cod = ['cod', 'cash_on_delivery', 'cash'];

        DB::table('orders')->orderBy('id')->chunkById(200, function ($orders) use ($centralFlashProducts, $cod) {
            foreach ($orders as $order) {
                $items = DB::table('order_items')->where('order_id', $order->id)->get();

                // Legacy grand total: Σ sub_total − pct% of it + shipping.
                $legacySub = (float) $items->sum('sub_total');
                $couponDiscount = round($legacySub * ((float) $order->discount_percentage / 100), 2);
                $legacyGrand = round($legacySub - $couponDiscount + (float) $order->shipping_charge, 2);

                foreach ($items as $item) {
                    $qty = max(1, (int) $item->qty);
                    $discount = (float) $item->discount;
                    $fixed = (float) $item->fixed_shipping;
                    $cost = (float) $item->cost;
                    // Legacy price = flash-discounted unit price incl. fixed shipping.
                    $unitBase = (float) $item->price + $discount / $qty;
                    $price = round(max(0.0, $unitBase - $fixed), 2);
                    $profit = round(max(0.0, $price - $cost), 2);
                    $subTotal = round($price * $qty, 2);
                    $grand = round($subTotal + $fixed - $discount + (float) $item->tax + (float) $item->shipping_fee, 2);

                    DB::table('order_items')->where('id', $item->id)->update([
                        'price' => $price,
                        'profit_amount' => $profit,
                        'sub_total' => $subTotal,
                        'discount_source' => $discount > 0
                            ? (isset($centralFlashProducts[$item->product_id]) ? 'central' : 'vendor')
                            : null,
                        'grand_total' => $grand,
                    ]);
                }

                $collectedByVendor = $order->vendor_gateway_id !== null
                    || (float) $order->vendor_cost > 0
                    || in_array(strtolower((string) $order->payment_method), $cod, true);

                $platform = $collectedByVendor && (float) $order->vendor_cost > 0
                    ? (float) $order->vendor_cost
                    : (float) $order->owner_profit;
                $platform = round($platform, 2);

                // Orders whose lines carry a real cost are re-expressed with the new formula
                // (fixed shipping now belongs to the platform share).
                if ($items->contains(fn ($i) => (float) $i->cost > 0)) {
                    $centralCoupon = $couponDiscount > 0 && $this->couponIsCentral($order->discount_id);
                    $platform = round(
                        $items->sum(function ($i) use ($centralFlashProducts) {
                            $centralDiscount = ((float) $i->discount > 0 && isset($centralFlashProducts[$i->product_id])) ? (float) $i->discount : 0.0;

                            return (float) $i->cost * (int) $i->qty + (float) $i->fixed_shipping - $centralDiscount;
                        })
                        + (float) $order->shipping_charge + (float) $items->sum('tax')
                        - ($centralCoupon ? $couponDiscount : 0.0),
                        2
                    );
                }

                DB::table('orders')->where('id', $order->id)->update([
                    'coupon_discount' => $couponDiscount,
                    'coupon_source' => $couponDiscount > 0
                        ? ($this->couponIsCentral($order->discount_id) ? 'central' : 'vendor')
                        : null,
                    'collected_by' => $collectedByVendor ? 'vendor' : 'platform',
                    'platform_profit' => $platform,
                    'vendor_profit' => round($legacyGrand - $platform, 2),
                ]);
            }
        });
    }

    private function couponIsCentral(?int $couponId): bool
    {
        if ($couponId === null || ! Schema::hasTable('coupons')) {
            return false;
        }

        return DB::table('coupons')->where('id', $couponId)->whereNotNull('central_coupon_id')->exists();
    }

    /** Fix the physical column order of order_items (MySQL only). */
    private function orderItemColumns(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $definitions = [
            'qty' => 'int UNSIGNED NOT NULL DEFAULT 1',
            'cost' => 'decimal(10,2) NOT NULL DEFAULT 0.00',
            'profit_amount' => 'decimal(10,2) NOT NULL DEFAULT 0.00',
            'price' => 'decimal(10,2) NOT NULL DEFAULT 0.00',
            'sub_total' => 'decimal(10,2) NOT NULL DEFAULT 0.00',
            'fixed_shipping' => 'decimal(10,2) NOT NULL DEFAULT 0.00',
            'discount' => 'decimal(10,2) NOT NULL DEFAULT 0.00',
            'discount_source' => 'varchar(10) NULL',
            'tax' => 'decimal(10,2) NOT NULL DEFAULT 0.00',
            'shipping_fee' => 'decimal(10,2) NOT NULL DEFAULT 0.00',
            'weight' => 'decimal(10,3) NOT NULL DEFAULT 0.000',
            'grand_total' => 'decimal(12,2) NOT NULL DEFAULT 0.00',
        ];

        $after = 'product_variant_id';
        foreach ($definitions as $column => $definition) {
            DB::statement("ALTER TABLE `order_items` MODIFY `{$column}` {$definition} AFTER `{$after}`");
            $after = $column;
        }
    }
};
