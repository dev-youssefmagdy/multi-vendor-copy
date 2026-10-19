<?php

declare(strict_types=1);

namespace Tests\Unit\Orders;

use App\Support\OrderPricing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** docs/ORDER_FORMULAS.md worked example + invariants. */
class OrderPricingTest extends TestCase
{
    #[Test]
    public function unit_price_is_cost_plus_profit(): void
    {
        $price = OrderPricing::unitPrice(1.72, 'percentage', 25, 200);

        $this->assertSame(0.43, $price['profit_amount']);
        $this->assertSame(2.15, $price['price']);
        $this->assertSame(200.0, $price['fixed_shipping']);

        $this->assertSame(3.72, OrderPricing::unitPrice(1.72, 'fixed', 2, 0)['price']);
    }

    #[Test]
    public function documented_example(): void
    {
        $item = OrderPricing::item(1.72, 0.43, 1, 200, 10, OrderPricing::SOURCE_VENDOR);

        $this->assertSame(2.15, $item['price']);
        $this->assertSame(2.15, $item['sub_total']);
        $this->assertSame(20.22, $item['discount']);   // (2.15 + 200) * 10 % = 20.215 -> half-up
        $this->assertSame(181.93, $item['grand_total']);

        $order = OrderPricing::order([$item], 1.2);

        $this->assertSame(183.13, $order['grand_total']);
        $this->assertSame($order['grand_total'], round($order['platform_profit'] + $order['vendor_profit'], 2));
        // vendor flash sale: platform keeps cost + fixed + shipping, vendor eats the whole discount.
        $this->assertSame(202.92, $order['platform_profit']);
        $this->assertSame(-19.79, $order['vendor_profit']);
    }

    #[Test]
    public function central_discounts_reduce_the_platform_share_only(): void
    {
        $item = OrderPricing::item(10, 2.5, 2, 5, 10, OrderPricing::SOURCE_CENTRAL);
        // sub_total 25, fixed 5, discount 3, grand 27
        $this->assertSame(27.0, $item['grand_total']);

        $order = OrderPricing::order([$item], 4, 2, OrderPricing::SOURCE_CENTRAL);

        $this->assertSame(29.0, $order['grand_total']);          // 27 + 4 - 2
        $this->assertSame(24.0, $order['platform_profit']);      // 20 + 5 - 3 + 4 - 2
        $this->assertSame(5.0, $order['vendor_profit']);         // 2.5 * 2
        $this->assertSame($order['grand_total'], round($order['platform_profit'] + $order['vendor_profit'], 2));
    }

    #[Test]
    public function vendor_coupon_and_tax_are_split_correctly(): void
    {
        $item = OrderPricing::item(10, 5, 1, 0, 0, null, 1.5);
        $order = OrderPricing::order([$item], 0, 3, OrderPricing::SOURCE_VENDOR);

        $this->assertSame(13.5, $order['grand_total']);   // 15 + 1.5 - 3
        $this->assertSame(11.5, $order['platform_profit']); // cost 10 + tax 1.5
        $this->assertSame(2.0, $order['vendor_profit']);    // 5 - 3
        $this->assertSame('vendor', $order['coupon_source']);
    }

    #[Test]
    public function coupon_discount_helper(): void
    {
        $this->assertSame(20.0, OrderPricing::couponDiscount('percentage', 10, 200));
        $this->assertSame(50.0, OrderPricing::couponDiscount('fixed', 80, 50));
    }
}
