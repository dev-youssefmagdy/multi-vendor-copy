<?php

declare(strict_types=1);

namespace Tests\Unit\Refunds;

use App\Enums\OrderStatus;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\OrderTransaction;
use App\Support\OrderProfitCalculator;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Cancelled / refunded orders are excluded and refunds subtracted in the profit helpers. */
class OrderProfitCalculatorRefundsTest extends TestCase
{
    /** @return iterable<string, array{OrderStatus, float, bool}> */
    public static function voidCases(): iterable
    {
        yield 'processing' => [OrderStatus::Processing, 0, false];
        yield 'delivered, partly refunded' => [OrderStatus::Delivered, 40, false];
        yield 'delivered, fully refunded' => [OrderStatus::Delivered, 100, true];
        yield 'cancelled' => [OrderStatus::Cancelled, 0, true];
        yield 'rejected' => [OrderStatus::Rejected, 0, true];
        yield 'refunded' => [OrderStatus::Refunded, 100, true];
    }

    #[Test]
    #[DataProvider('voidCases')]
    public function void_orders_produce_no_money(OrderStatus $status, float $refunded, bool $void): void
    {
        // Platform-collected order: grand 100, platform_profit 30, vendor_profit 70.
        $order = $this->order($status, $refunded);

        $this->assertSame($void, OrderProfitCalculator::isFinanciallyVoid($order));

        if ($void) {
            $this->assertSame(0.0, OrderProfitCalculator::netOrderTotal($order));
            $this->assertSame(0.0, OrderProfitCalculator::platformProfitForOrder($order));
            $this->assertSame(0.0, OrderProfitCalculator::vendorProfitForOrder($order));
            $this->assertSame(0.0, OrderProfitCalculator::platformOwnVendorForOrder($order));
            $this->assertSame(0.0, OrderProfitCalculator::vendorOwnPlatformForOrder($order));

            return;
        }

        $this->assertSame(100.0 - $refunded, OrderProfitCalculator::netOrderTotal($order));
        $this->assertSame(30.0, OrderProfitCalculator::platformProfitForOrder($order), 'platform profit is not reduced by partial refunds');
        $this->assertSame(70.0 - $refunded, OrderProfitCalculator::vendorProfitForOrder($order));
        $this->assertSame(70.0 - $refunded, OrderProfitCalculator::platformOwnVendorForOrder($order));
    }

    #[Test]
    public function vendor_collected_order_owes_platform_nothing_once_cancelled(): void
    {
        $order = $this->order(OrderStatus::Processing, 0, ['collected_by' => 'vendor', 'platform_profit' => 25]);
        $this->assertSame(25.0, OrderProfitCalculator::vendorOwnPlatformForOrder($order));

        $order->status = OrderStatus::Cancelled;
        $this->assertSame(0.0, OrderProfitCalculator::vendorOwnPlatformForOrder($order));
    }

    #[Test]
    public function settlements_and_payouts_reduce_the_running_balances(): void
    {
        $vendorCollected = $this->order(OrderStatus::Processing, 0, ['collected_by' => 'vendor', 'platform_profit' => 30]);
        $vendorCollected->setRelation('transactions', new Collection([
            new OrderTransaction(['type' => OrderTransaction::TYPE_VENDOR_SETTLEMENT, 'amount' => 10]),
        ]));
        $this->assertSame(20.0, OrderProfitCalculator::vendorOwnPlatformForOrder($vendorCollected));
        $this->assertSame(0.0, OrderProfitCalculator::platformOwnVendorForOrder($vendorCollected));

        $platformCollected = $this->order(OrderStatus::Processing, 0);
        $platformCollected->setRelation('transactions', new Collection([
            new OrderTransaction(['type' => OrderTransaction::TYPE_PLATFORM_PAYOUT, 'amount' => 70]),
        ]));
        $this->assertSame(0.0, OrderProfitCalculator::platformOwnVendorForOrder($platformCollected));
    }

    private function order(OrderStatus $status, float $refunded, array $attributes = []): Order
    {
        $order = new Order(array_merge([
            'status' => $status,
            'paid' => true,
            'platform_profit' => 30,
            'vendor_profit' => 70,
            'collected_by' => 'platform',
            'coupon_discount' => 0,
            'refunded_amount' => $refunded,
            'discount_percentage' => 0,
            'tax_percentage' => 0,
            'shipping_charge' => 0,
        ], $attributes));

        return $order->setRelation('items', new Collection([
            new OrderItem(['qty' => 1, 'price' => 100, 'sub_total' => 100, 'discount' => 0, 'tax' => 0, 'shipping_fee' => 0, 'grand_total' => 100]),
        ]));
    }
}
