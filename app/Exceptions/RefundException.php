<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * A refund rule was violated (unpaid order, double refund, wrong refund state, …).
 * The message is user-safe and can be shown to vendors / admins / customers as is.
 * Rendered as a 422 (JSON) or a redirect back with the message as an error.
 */
class RefundException extends RuntimeException
{
    public const NOT_PAID = 'not_paid';

    public const ORDER_REFUNDED = 'order_refunded';

    public const EXCEEDS_REFUNDABLE = 'exceeds_refundable';

    public const EXCEEDS_CALCULATED = 'exceeds_calculated';

    public const INVALID_AMOUNT = 'invalid_amount';

    public const INVALID_STATE = 'invalid_state';

    public const MANUAL_ONLY = 'manual_only';

    public const ALREADY_REQUESTED = 'already_requested';

    public const NOT_FOUND = 'not_found';

    public function __construct(string $message, public readonly string $reason = self::INVALID_STATE)
    {
        parent::__construct($message);
    }

    public static function notPaid(): self
    {
        return new self(__('This order has not been paid, so there is nothing to refund.'), self::NOT_PAID);
    }

    public static function orderRefunded(): self
    {
        return new self(__('This order has already been refunded.'), self::ORDER_REFUNDED);
    }

    public static function exceedsRefundable(float $remaining): self
    {
        return new self(
            __('The refund exceeds the amount that can still be refunded on this order (:amount).', ['amount' => number_format($remaining, 2)]),
            self::EXCEEDS_REFUNDABLE,
        );
    }

    public static function exceedsCalculated(float $max): self
    {
        return new self(
            __('The refund cannot be higher than the calculated maximum of :amount.', ['amount' => number_format($max, 2)]),
            self::EXCEEDS_CALCULATED,
        );
    }

    public static function invalidAmount(): self
    {
        return new self(__('The refund amount must be greater than zero.'), self::INVALID_AMOUNT);
    }

    /** @param 'executed'|'retried'|'completed'|'rejected' $action */
    public static function invalidState(string $action): self
    {
        return new self(match ($action) {
            'executed' => __('Only pending refunds can be executed.'),
            'retried' => __('Only failed refunds can be retried.'),
            'completed' => __('Only pending or failed refunds can be completed manually.'),
            'rejected' => __('Only pending or failed refunds can be rejected.'),
            default => __('This refund can no longer be changed.'),
        }, self::INVALID_STATE);
    }

    public static function manualOnly(): self
    {
        return new self(__('This refund must be settled outside the system and then marked as completed manually.'), self::MANUAL_ONLY);
    }

    public static function alreadyRequested(): self
    {
        return new self(__('A refund has already been issued for this return request.'), self::ALREADY_REQUESTED);
    }

    public static function notFound(): self
    {
        return new self(__('The order for this refund could not be found.'), self::NOT_FOUND);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
                'code' => $this->reason,
            ], 422);
        }

        return back()->withErrors(['refund' => $this->getMessage()]);
    }
}
