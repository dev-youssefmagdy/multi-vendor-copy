<?php

declare(strict_types=1);

namespace App\Support\Tenant\Refunds;

use App\Enums\RefundMethod;
use App\Enums\RefundStatus;
use App\Models\Refund;

/** Panel (vendor) view of a refund, incl. the actions its state allows. */
final class RefundPresenter
{
    /** @return array<string, mixed> */
    public static function panel(Refund $refund): array
    {
        $open = $refund->status instanceof RefundStatus && $refund->status->isOpen() && $refund->status !== RefundStatus::Processing;

        return [
            'id' => $refund->id,
            'reference' => $refund->reference,
            'order_number' => $refund->order_number,
            'return_request_id' => $refund->return_request_id ? (int) $refund->return_request_id : null,
            'source' => $refund->source?->value,
            'source_label' => $refund->source?->label(),
            'reason' => $refund->reason,
            'currency' => $refund->currency,
            'items_amount' => round((float) $refund->items_amount, 2),
            'shipping_amount' => round((float) $refund->shipping_amount, 2),
            'return_fee' => round((float) $refund->return_fee, 2),
            'amount' => round((float) $refund->amount, 2),
            'method' => $refund->refund_method?->value,
            'method_label' => $refund->refund_method?->label(),
            'gateway' => $refund->gateway,
            'gateway_refund_id' => $refund->gateway_refund_id,
            'manual_reference' => $refund->meta['manual_reference'] ?? null,
            'status' => $refund->status?->value,
            'status_label' => $refund->status?->label(),
            'status_color' => $refund->status?->color(),
            'failure_reason' => $refund->failure_reason,
            'requested_by' => $refund->requested_by_type?->value,
            'approved_by' => $refund->approved_by_name ?? $refund->approved_by_type?->label(),
            'requested_at' => $refund->requested_at?->toIso8601String(),
            'approved_at' => $refund->approved_at?->toIso8601String(),
            'processed_at' => $refund->processed_at?->toIso8601String(),
            // Pending → send to the gateway, failed → send again (RefundController::retry handles both).
            'can_retry' => in_array($refund->status, [RefundStatus::Pending, RefundStatus::Failed], true)
                && $refund->refund_method === RefundMethod::OriginalPayment,
            'can_complete' => $open,
            'can_reject' => $open,
        ];
    }
}
