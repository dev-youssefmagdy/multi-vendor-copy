<?php

declare(strict_types=1);

namespace App\Support\Tenant\Storefront;

use App\Enums\CancellationActor;
use App\Enums\RefundStatus;
use App\Models\Refund;
use App\Models\Tenant\Order;
use Illuminate\Support\Carbon;

/**
 * Customer-facing (storefront) view data for an order's cancellation and refunds
 * (RETURN_EXCHANGE_REFUND_PLAN.md B.3.4 / B.8 Customer). Used by OrderStatusPage /
 * ReturnDetailPage and read by the shared partials order-cancellation-summary and
 * order-refunds-summary. Internal fields (gateway ids, failure details, approver) are left out.
 */
final class OrderAfterSalesPresenter
{
    /**
     * The cancellation summary; null when the order is not cancelled.
     *
     * @return array{reason: string|null, reason_label: string|null, note: string|null, cancelled_at: Carbon|null, cancelled_by: string|null, cancelled_by_label: string|null}|null
     */
    public static function cancellation(Order $order): ?array
    {
        if (! $order->isCancelled()) {
            return null;
        }

        $actor = $order->cancelled_by_type instanceof CancellationActor ? $order->cancelled_by_type : null;

        return [
            'reason' => $order->cancellation_reason?->value,
            'reason_label' => $order->cancellation_reason?->label(),
            'note' => $order->cancellation_note,
            'cancelled_at' => $order->cancelled_at ?? ($order->cancellation_reason ? $order->updated_at : null),
            'cancelled_by' => $actor?->value,
            'cancelled_by_label' => $actor?->customerFacingLabel(),
        ];
    }

    /**
     * The order's refunds, newest first.
     *
     * @return list<array<string, mixed>>
     */
    public static function refundsFor(Order $order, ?string $tenantId = null): array
    {
        return self::refunds($order->refundsQuery($tenantId)->latest('id')->get());
    }

    /**
     * @param  iterable<Refund>  $refunds
     * @return list<array<string, mixed>>
     */
    public static function refunds(iterable $refunds): array
    {
        $rows = [];

        foreach ($refunds as $refund) {
            $rows[] = self::refund($refund);
        }

        return $rows;
    }

    /**
     * @return array{reference: string, amount: float, currency: string|null, method: string|null, method_label: string|null, status: string|null, status_label: string|null, status_color: string, requested_at: Carbon|null, processed_at: Carbon|null, rejection_reason: string|null, return_request_id: int|null}
     */
    public static function refund(Refund $refund): array
    {
        $status = $refund->status instanceof RefundStatus ? $refund->status : null;

        return [
            'reference' => (string) $refund->reference,
            'amount' => round((float) $refund->amount, 2),
            'currency' => $refund->currency,
            'method' => $refund->refund_method?->value,
            'method_label' => $refund->refund_method?->label(),
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'status_color' => $status?->color() ?? 'amber',
            'requested_at' => $refund->requested_at ?? $refund->created_at,
            // processed_at is also stamped for failed attempts; the customer only sees a completion date.
            'processed_at' => $status === RefundStatus::Completed ? $refund->processed_at : null,
            'rejection_reason' => $status === RefundStatus::Rejected ? $refund->failure_reason : null,
            'return_request_id' => $refund->return_request_id ? (int) $refund->return_request_id : null,
        ];
    }
}
