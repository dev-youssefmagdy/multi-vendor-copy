<?php

declare(strict_types=1);

namespace Tests\Unit\Refunds;

use App\Enums\OrderStatus;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
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
        // Central-gateway order: grand 100, owner profit 30.
        $order = $this->order($status, $refunded);

        $this->assertSame($void, OrderProfitCalculator::isFinanciallyVoid($order));

        if ($void) {
            $this->assertSame(0.0, OrderProfitCalculator::netOrderTotal($order));
            $this->assertSame(0.0, OrderProfitCalculator::effectiveOwnerProfitForOrder($order));
            $this->assertSame(0.0, OrderProfitCalculator::effectiveTenantProfitForOrder($order));
            $this->assertSame(0.0, OrderProfitCalculator::centralOwnTenantForOrder($order));
            $this->assertSame(0.0, OrderProfitCalculator::tenantOwnCentralForOrder($order));

            return;
        }

        $this->assertSame(100.0 - $refunded, OrderProfitCalculator::netOrderTotal($order));
        $this->assertSame(30.0, OrderProfitCalculator::effectiveOwnerProfitForOrder($order), 'owner profit is not reduced by partial refunds');
        $this->assertSame(70.0 - $refunded, OrderProfitCalculator::effectiveTenantProfitForOrder($order));
        $this->assertSame(70.0 - $refunded, OrderProfitCalculator::centralOwnTenantForOrder($order));
    }

    #[Test]
    public function vendor_gateway_order_owes_central_nothing_once_cancelled(): void
    {
        $order = $this->order(OrderStatus::Processing, 0, ['vendor_gateway_id' => 3, 'vendor_cost' => 25]);
        $this->assertSame(25.0, OrderProfitCalculator::tenantOwnCentralForOrder($order));

        $order->status = OrderStatus::Cancelled;
        $this->assertSame(0.0, OrderProfitCalculator::tenantOwnCentralForOrder($order));
        $this->assertSame(0.0, OrderProfitCalculator::remainingTenantOwnCentralForOrder($order));
    }

    private function order(OrderStatus $status, float $refunded, array $attributes = []): Order
    {
        $order = new Order(array_merge([
            'status' => $status,
            'paid' => true,
            'owner_profit' => 30,
            'refunded_amount' => $refunded,
            'discount_percentage' => 0,
            'tax_percentage' => 0,
            'shipping_charge' => 0,
        ], $attributes));

        return $order->setRelation('items', new Collection([
            new OrderItem(['qty' => 1, 'price' => 100, 'sub_total' => 100, 'discount' => 0, 'tax' => 0, 'shipping_fee' => 0]),
        ]));
    }
}
