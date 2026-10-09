<?php

declare(strict_types=1);

namespace Tests\Unit\Refunds;

use App\Enums\OrderStatus;
use App\Enums\ReturnReason;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Services\Refunds\RefundCalculator;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pure RefundCalculator: in-memory orders (no DB). Order::grand_total =
 * Σ sub_total − order discount + order tax + shipping.
 */
class RefundCalculatorTest extends TestCase
{
    private RefundCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new RefundCalculator;
    }

    // ─── forCancellation ────────────────────────────────────────────────────

    #[Test]
    public function cancellation_refunds_the_whole_grand_total_for_a_paid_order(): void
    {
        // 2 × 40 + 1 × 20 = 100 subtotal, 10% discount (−10), 5% tax (+5), 15 shipping → 110.
        $order = $this->order([[40, 2], [20, 1]], discountPct: 10, taxPct: 5, shipping: 15);

        $result = $this->calculator->forCancellation($order);

        $this->assertSame(110.0, $order->grand_total);
        $this->assertSame(95.0, $result->itemsAmount);
        $this->assertSame(15.0, $result->shippingAmount);
        $this->assertSame(0.0, $result->returnFee);
        $this->assertSame(110.0, $result->amount);
        $this->assertSame(110.0, $result->max);
        $this->assertFalse($result->capped);
    }

    #[Test]
    public function cancellation_subtracts_completed_refunds_and_refunds_shipping_first(): void
    {
        $order = $this->order([[50, 2]], shipping: 10, refunded: 70); // grand 110, 40 left

        $result = $this->calculator->forCancellation($order);

        $this->assertSame(40.0, $result->amount);
        $this->assertSame(10.0, $result->shippingAmount);
        $this->assertSame(30.0, $result->itemsAmount);
        $this->assertSame(40.0, $result->remaining);
    }

    #[Test]
    public function cancellation_of_an_unpaid_or_fully_refunded_order_is_zero(): void
    {
        $this->assertSame(0.0, $this->calculator->forCancellation($this->order([[50, 1]], paid: false))->amount);

        $refunded = $this->calculator->forCancellation($this->order([[50, 1]], shipping: 5, refunded: 55));
        $this->assertSame(0.0, $refunded->amount);
        $this->assertSame(0.0, $refunded->itemsAmount);
        $this->assertSame(0.0, $refunded->shippingAmount);
    }

    // ─── forReturn ──────────────────────────────────────────────────────────

    /** @return iterable<string, array{0: list<array{0: float, 1: int}>, 1: int, 2: ReturnReason, 3: float, 4: array<string, float|int>, 5: array<string, float|bool>}> */
    public static function returnCases(): iterable
    {
        // lines, qty returned (of line 0), reason, policy fee, order attrs, expected
        yield 'customer reason pays the policy fee, no shipping' => [
            [[50, 2], [30, 1]], 1, ReturnReason::ChangedMind, 5.0, ['shipping' => 10],
            ['items' => 50.0, 'shipping' => 0.0, 'fee' => 5.0, 'amount' => 45.0, 'fee_waived' => false],
        ];

        yield 'seller fault waives the fee' => [
            [[50, 2], [30, 1]], 1, ReturnReason::Defective, 5.0, ['shipping' => 10],
            ['items' => 50.0, 'shipping' => 0.0, 'fee' => 0.0, 'amount' => 50.0, 'fee_waived' => true],
        ];

        yield 'seller fault + whole single-line order refunds shipping' => [
            [[50, 2]], 2, ReturnReason::WrongItem, 5.0, ['shipping' => 10],
            ['items' => 100.0, 'shipping' => 10.0, 'fee' => 0.0, 'amount' => 110.0, 'fee_waived' => true],
        ];

        yield 'customer reason + whole order does not refund shipping' => [
            [[50, 2]], 2, ReturnReason::SizeOrFit, 4.0, ['shipping' => 10],
            ['items' => 100.0, 'shipping' => 0.0, 'fee' => 4.0, 'amount' => 96.0, 'fee_waived' => false],
        ];

        yield 'seller fault but only part of a single-line order: no shipping' => [
            [[50, 3]], 1, ReturnReason::NotAsDescribed, 0.0, ['shipping' => 10],
            ['items' => 50.0, 'shipping' => 0.0, 'fee' => 0.0, 'amount' => 50.0, 'fee_waived' => false],
        ];

        yield 'order discount is prorated onto the line' => [
            [[40, 2], [20, 1]], 1, ReturnReason::Other, 0.0, ['discount' => 10],
            ['items' => 36.0, 'shipping' => 0.0, 'fee' => 0.0, 'amount' => 36.0, 'fee_waived' => false],
        ];

        yield 'order discount and tax are both prorated' => [
            [[40, 2], [20, 1]], 2, ReturnReason::Other, 0.0, ['discount' => 10, 'tax' => 5],
            ['items' => 76.0, 'shipping' => 0.0, 'fee' => 0.0, 'amount' => 76.0, 'fee_waived' => false],
        ];

        yield 'unit values are rounded once, at the end' => [
            [[33.33, 3]], 2, ReturnReason::Other, 0.0, ['discount' => 15],
            ['items' => 56.66, 'shipping' => 0.0, 'fee' => 0.0, 'amount' => 56.66, 'fee_waived' => false],
        ];

        yield 'fee larger than the goods floors at zero' => [
            [[3, 1], [10, 1]], 1, ReturnReason::ChangedMind, 5.0, [],
            ['items' => 3.0, 'shipping' => 0.0, 'fee' => 5.0, 'amount' => 0.0, 'fee_waived' => false],
        ];

        yield 'negative policy fee is ignored' => [
            [[25, 1], [10, 1]], 1, ReturnReason::ChangedMind, -3.0, [],
            ['items' => 25.0, 'shipping' => 0.0, 'fee' => 0.0, 'amount' => 25.0, 'fee_waived' => false],
        ];
    }

    /**
     * @param  list<array{0: float, 1: int}>  $lines
     * @param  array<string, float|int>  $attrs
     * @param  array<string, float|bool>  $expected
     */
    #[Test]
    #[DataProvider('returnCases')]
    public function return_breakdown(array $lines, int $qty, ReturnReason $reason, float $fee, array $attrs, array $expected): void
    {
        $order = $this->order(
            $lines,
            discountPct: (float) ($attrs['discount'] ?? 0),
            taxPct: (float) ($attrs['tax'] ?? 0),
            shipping: (float) ($attrs['shipping'] ?? 0),
        );

        $result = $this->calculator->forReturn($order, $order->items->first(), $qty, $reason, $fee);

        $this->assertSame($expected['items'], $result->itemsAmount, 'items_amount');
        $this->assertSame($expected['shipping'], $result->shippingAmount, 'shipping_amount');
        $this->assertSame($expected['fee'], $result->returnFee, 'return_fee');
        $this->assertSame($expected['amount'], $result->amount, 'amount');
        $this->assertSame($expected['amount'], $result->max, 'max');
        $this->assertSame($expected['fee_waived'], $result->feeWaived, 'fee_waived');
        $this->assertFalse($result->capped);
    }

    #[Test]
    public function returning_every_line_adds_up_to_the_grand_total(): void
    {
        $order = $this->order([[19.99, 3], [7.5, 2], [100, 1]], discountPct: 12.5, taxPct: 7, shipping: 9.99);

        $sum = 0.0;
        foreach ($order->items as $item) {
            $sum += $this->calculator->forReturn($order, $item, (int) $item->qty, ReturnReason::Other)->itemsAmount;
        }

        $this->assertEqualsWithDelta($order->grand_total - 9.99, $sum, 0.02);
    }

    #[Test]
    public function explicit_whole_order_flag_overrides_inference(): void
    {
        $order = $this->order([[50, 1], [30, 1]], shipping: 12);
        $last = $order->items->last();

        // Inferred: two lines → not the whole order.
        $this->assertSame(0.0, $this->calculator->forReturn($order, $last, 1, ReturnReason::Defective)->shippingAmount);

        // The first line was already returned earlier → the caller says this completes the order.
        $whole = $this->calculator->forReturn($order, $last, 1, ReturnReason::Defective, 0.0, wholeOrder: true);
        $this->assertSame(12.0, $whole->shippingAmount);
        $this->assertSame(42.0, $whole->amount);

        // …but never for a customer-fault reason.
        $this->assertSame(0.0, $this->calculator->forReturn($order, $last, 1, ReturnReason::ChangedMind, 0.0, wholeOrder: true)->shippingAmount);

        // And an explicit false wins over the single-line inference.
        $single = $this->order([[50, 1]], shipping: 12);
        $this->assertSame(0.0, $this->calculator->forReturn($single, $single->items->first(), 1, ReturnReason::Defective, 0.0, wholeOrder: false)->shippingAmount);
    }

    #[Test]
    public function the_amount_is_capped_at_what_is_left_on_the_order(): void
    {
        // grand 110; 80 already refunded → 30 left; the return is worth 50.
        $order = $this->order([[50, 2]], shipping: 10, refunded: 80);

        $result = $this->calculator->forReturn($order, $order->items->first(), 1, ReturnReason::Defective);

        $this->assertSame(50.0, $result->itemsAmount);
        $this->assertSame(30.0, $result->amount);
        $this->assertSame(30.0, $result->max);
        $this->assertSame(30.0, $result->remaining);
        $this->assertTrue($result->capped);
    }

    #[Test]
    public function an_unpaid_order_has_nothing_to_refund_on_return(): void
    {
        $order = $this->order([[50, 2]], paid: false);

        $result = $this->calculator->forReturn($order, $order->items->first(), 1, ReturnReason::ChangedMind);

        $this->assertSame(50.0, $result->itemsAmount);
        $this->assertSame(0.0, $result->amount);
        $this->assertTrue($result->capped);
    }

    #[Test]
    public function a_delivered_cash_on_delivery_order_counts_as_collected(): void
    {
        $order = $this->order([[50, 2]], shipping: 10, paid: false);
        $order->payment_method = 'cod';

        // Not delivered yet: the courier has not collected the cash.
        $this->assertFalse($order->isPaymentCollected());
        $this->assertSame(0.0, $this->calculator->forCancellation($order)->amount);

        foreach ([OrderStatus::Delivered, OrderStatus::Completed] as $status) {
            $order->status = $status;

            $this->assertTrue($order->isPaymentCollected(), $status->value);
            $this->assertFalse((bool) $order->paid, 'the paid flag is left untouched');
            $this->assertSame(110.0, $order->remainingRefundable());
            $this->assertSame(50.0, $this->calculator->forReturn($order, $order->items->first(), 1, ReturnReason::ChangedMind)->amount);
        }

        // An unpaid online order is never "collected", whatever its status.
        $order->payment_method = 'stripe';
        $this->assertFalse($order->isPaymentCollected());
        $this->assertSame(0.0, $order->remainingRefundable());
    }

    #[Test]
    public function the_flash_sale_discount_column_is_not_subtracted_twice(): void
    {
        // sub_total is already at the flash price (2 × 40); `discount` only records the savings.
        $order = $this->order([[40, 2]], itemDiscount: 20);

        $this->assertSame(80.0, $order->grand_total);
        $this->assertSame(80.0, $this->calculator->forReturn($order, $order->items->first(), 2, ReturnReason::Other)->amount);
    }

    #[Test]
    public function invalid_quantities_are_rejected(): void
    {
        $order = $this->order([[50, 2]]);

        foreach ([0, 3, -1] as $qty) {
            try {
                $this->calculator->forReturn($order, $order->items->first(), $qty, ReturnReason::Other);
                $this->fail("Quantity {$qty} should be rejected.");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function breakdown_serialises_to_an_array(): void
    {
        $order = $this->order([[50, 1]], shipping: 5);

        $this->assertSame([
            'items_amount' => 50.0,
            'shipping_amount' => 5.0,
            'return_fee' => 0.0,
            'amount' => 55.0,
            'max' => 55.0,
            'remaining' => 55.0,
            'capped' => false,
            'fee_waived' => false,
        ], $this->calculator->forCancellation($order)->toArray());
    }

    /** @param list<array{0: float, 1: int}> $lines [unit price, qty] */
    private function order(
        array $lines,
        float $discountPct = 0,
        float $taxPct = 0,
        float $shipping = 0,
        bool $paid = true,
        float $refunded = 0,
        float $itemDiscount = 0,
    ): Order {
        $order = new Order([
            'uuid' => 'order-test',
            'status' => OrderStatus::Processing,
            'paid' => $paid,
            'discount_percentage' => $discountPct,
            'tax_percentage' => $taxPct,
            'shipping_charge' => $shipping,
            'refunded_amount' => $refunded,
        ]);

        $items = collect($lines)->values()->map(function (array $line, int $index) use ($itemDiscount) {
            $item = new OrderItem([
                'qty' => $line[1],
                'price' => $line[0],
                'sub_total' => round($line[0] * $line[1], 2),
                'discount' => $index === 0 ? $itemDiscount : 0,
                'tax' => 0,
                'shipping_fee' => 0,
            ]);
            $item->id = $index + 1;

            return $item;
        });

        return $order->setRelation('items', new Collection($items->all()));
    }
}
