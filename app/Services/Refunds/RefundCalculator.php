<?php

declare(strict_types=1);

namespace App\Services\Refunds;

use App\Enums\ReturnReason;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use InvalidArgumentException;

/**
 * Pure refundable-amount calculator (RETURN_EXCHANGE_REFUND_PLAN.md B.4). No DB access:
 * it only reads the given order (with its `items` relation) and item.
 *
 * Line values follow Order::grand_total: an item's `sub_total` is already net of its
 * item-level (flash-sale) discount — `discount` only records the savings — and item tax is
 * already included, so the net value of a line is
 *     sub_total × (1 − order discount % + order tax %)
 * which makes Σ lines + shipping = grand_total.
 */
class RefundCalculator
{
    /**
     * Full refund of what is left on a cancelled order: items = subtotal − order discount
     * + order tax, shipping = shipping charge, no fee; amount = grand_total − refunded_amount.
     */
    public function forCancellation(Order $order): RefundBreakdown
    {
        $items = round($order->subtotal - $order->discount_amount + $order->tax_amount, 2);
        $shipping = round($order->resolved_shipping_charge, 2);
        $remaining = $order->remainingRefundable();
        $amount = $remaining;

        // Partly refunded already: report the breakdown of what is actually left,
        // refunding the shipping first and the remainder as goods.
        if ($amount < round($items + $shipping, 2)) {
            $shipping = min($shipping, $amount);
            $items = round($amount - $shipping, 2);
        }

        return new RefundBreakdown(
            itemsAmount: max(0.0, $items),
            shippingAmount: max(0.0, $shipping),
            returnFee: 0.0,
            amount: $amount,
            max: $amount,
            remaining: $remaining,
            capped: false,
        );
    }

    /**
     * Refund for returning $qty units of $item.
     *
     * - items_amount = net line value (incl. the prorated order discount / tax) × qty / item qty
     * - shipping is refunded only for a seller-fault reason when the whole order comes back
     * - the policy fee is waived for seller-fault reasons
     * - amount = max(0, items + shipping − fee), capped at the order's remaining refundable amount
     *
     * @param  bool|null  $wholeOrder  Whether this return completes the return of the whole order.
     *                                 null = infer: the item is the order's only line and every unit comes back.
     */
    public function forReturn(
        Order $order,
        OrderItem $item,
        int $qty,
        ReturnReason $reason,
        float $policyFee = 0.0,
        ?bool $wholeOrder = null,
    ): RefundBreakdown {
        $itemQty = max(1, (int) $item->qty);

        if ($qty < 1 || $qty > $itemQty) {
            throw new InvalidArgumentException("Return quantity must be between 1 and {$itemQty}.");
        }

        $orderAdjustment = 1 - ((float) $order->discount_percentage / 100) + ((float) $order->tax_percentage / 100);
        $lineNet = (float) $item->sub_total * $orderAdjustment;
        $itemsAmount = max(0.0, round($lineNet * $qty / $itemQty, 2));

        $wholeOrder ??= $order->items->count() <= 1 && $qty >= $itemQty;
        $sellerFault = $reason->isSellerFault();

        $shippingAmount = ($sellerFault && $wholeOrder) ? round($order->resolved_shipping_charge, 2) : 0.0;

        $policyFee = max(0.0, round($policyFee, 2));
        $returnFee = $sellerFault ? 0.0 : $policyFee;

        $gross = max(0.0, round($itemsAmount + $shippingAmount - $returnFee, 2));
        $remaining = $order->remainingRefundable();
        $amount = min($gross, $remaining);

        return new RefundBreakdown(
            itemsAmount: $itemsAmount,
            shippingAmount: $shippingAmount,
            returnFee: $returnFee,
            amount: $amount,
            max: $amount,
            remaining: $remaining,
            capped: $amount < $gross,
            feeWaived: $sellerFault && $policyFee > 0,
        );
    }
}
