<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\CancellationActor;
use App\Enums\OrderStatus;
use App\Models\Tenant\Order;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Who may cancel an order in which status (RETURN_EXCHANGE_REFUND_PLAN.md B.3.1). Pure: it only
 * reads the given order (status, created_at) and the given policy values — no DB access.
 *
 * | Status               | Customer                            | Vendor / Admin / System |
 * |----------------------|-------------------------------------|-------------------------|
 * | Pending              | yes                                 | yes                     |
 * | Processing           | if cancellation_allow_processing    | yes                     |
 * |                      | and within cancellation_window_hours|                         |
 * |                      | (0 = no limit) of order placement   |                         |
 * | Shipped              | no → return after delivery          | no                      |
 * | Delivered/Completed  | no → return                         | no                      |
 * | Cancelled/Rejected   | no                                  | no                      |
 * | Refunded             | no                                  | no                      |
 */
class OrderCancellationPolicy
{
    /**
     * @param  array<string, bool|int>|OrderPolicyService  $policy  OrderPolicyService::all() values (missing keys fall
     *                                                              back to OrderPolicyService::DEFAULTS), or the
     *                                                              service itself (current tenant's policy)
     * @param  CarbonInterface|null  $now  reference time for the processing window (default: now)
     */
    public function evaluate(
        Order $order,
        CancellationActor $actor,
        array|OrderPolicyService $policy = [],
        ?CarbonInterface $now = null,
    ): CancellationDecision {
        $policy = $policy instanceof OrderPolicyService ? $policy->all() : array_replace(OrderPolicyService::DEFAULTS, $policy);
        $status = $order->status instanceof OrderStatus ? $order->status : OrderStatus::tryFrom((string) $order->status);

        return match ($status) {
            OrderStatus::Pending, null => CancellationDecision::allow(),
            OrderStatus::Processing => $actor === CancellationActor::Customer
                ? $this->customerProcessing($order, $policy, $now)
                : CancellationDecision::allow(),
            OrderStatus::Shipped => CancellationDecision::deny(
                CancellationDecision::SHIPPED,
                __("This order has already been shipped. Once it's delivered you can request a return."),
                suggestReturn: true,
            ),
            OrderStatus::Delivered, OrderStatus::Completed => CancellationDecision::deny(
                CancellationDecision::DELIVERED,
                __('This order was delivered — please request a return instead.'),
                suggestReturn: true,
            ),
            OrderStatus::Cancelled, OrderStatus::Rejected => CancellationDecision::deny(
                CancellationDecision::ALREADY_CANCELLED,
                __('This order is already cancelled.'),
            ),
            OrderStatus::Refunded => CancellationDecision::deny(
                CancellationDecision::ALREADY_REFUNDED,
                __('This order has already been refunded.'),
            ),
        };
    }

    /** Shortcut for UIs: may the actor cancel the order right now? */
    public function canCancel(Order $order, CancellationActor $actor, array|OrderPolicyService $policy = [], ?CarbonInterface $now = null): bool
    {
        return $this->evaluate($order, $actor, $policy, $now)->allowed;
    }

    /** @param array<string, bool|int> $policy */
    private function customerProcessing(Order $order, array $policy, ?CarbonInterface $now): CancellationDecision
    {
        $denied = CancellationDecision::deny(
            CancellationDecision::PROCESSING_LOCKED,
            __('This order is already being prepared and can no longer be cancelled. Please contact the store.'),
        );

        if (! (bool) $policy[OrderPolicyService::CANCELLATION_ALLOW_PROCESSING]) {
            return $denied;
        }

        $windowHours = max(0, (int) $policy[OrderPolicyService::CANCELLATION_WINDOW_HOURS]);

        if ($windowHours === 0 || $order->created_at === null) {
            return CancellationDecision::allow();
        }

        $deadline = Carbon::parse($order->created_at)->addHours($windowHours);

        return ($now ?? Carbon::now())->lessThanOrEqualTo($deadline) ? CancellationDecision::allow() : $denied;
    }
}
