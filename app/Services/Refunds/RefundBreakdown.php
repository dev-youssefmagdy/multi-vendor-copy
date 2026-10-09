<?php

declare(strict_types=1);

namespace App\Services\Refunds;

/**
 * Result of RefundCalculator: the refund amount and how it is made up.
 *
 * amount = max(0, itemsAmount + shippingAmount − returnFee), capped at the order's
 * remaining refundable amount. `max` is the highest amount a reviewer may approve
 * (equal to `amount`; a reviewer may lower it, never raise it).
 */
final readonly class RefundBreakdown
{
    public function __construct(
        public float $itemsAmount,
        public float $shippingAmount,
        public float $returnFee,
        public float $amount,
        public float $max,
        /** What could still be refunded on the order (completed refunds only) when calculated. */
        public float $remaining,
        /** The amount was reduced to the order's remaining refundable amount. */
        public bool $capped = false,
        /** A return fee applied by policy was waived (seller-fault reason). */
        public bool $feeWaived = false,
    ) {}

    /** @return array{items_amount: float, shipping_amount: float, return_fee: float, amount: float, max: float, remaining: float, capped: bool, fee_waived: bool} */
    public function toArray(): array
    {
        return [
            'items_amount' => $this->itemsAmount,
            'shipping_amount' => $this->shippingAmount,
            'return_fee' => $this->returnFee,
            'amount' => $this->amount,
            'max' => $this->max,
            'remaining' => $this->remaining,
            'capped' => $this->capped,
            'fee_waived' => $this->feeWaived,
        ];
    }
}
