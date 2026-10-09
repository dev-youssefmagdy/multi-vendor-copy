<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\CancellationActor;
use App\Enums\CancellationReason;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CancelOrderRequest;
use App\Models\Refund;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Orders\OrderCancellationPolicy;
use App\Services\Orders\OrderCancellationService;
use App\Services\Orders\OrderPolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /** GET /orders */
    public function index(Request $request): JsonResponse
    {
        $orders = app(StorefrontRepository::class)->customerOrders($this->customer());

        if ($status = $request->query('status')) {
            $statusEnum = OrderStatus::tryFrom($status);
            if ($statusEnum) {
                $orders = $orders->filter(fn (Order $o) => $o->status === $statusEnum)->values();
            }
        }

        return response()->json(['data' => $orders->map(fn (Order $o) => $this->transformSummary($o))->values()]);
    }

    /** GET /orders/{uuid} */
    public function show(string $uuid): JsonResponse
    {
        $order = Order::where('uuid', $uuid)
            ->where('customer_id', $this->customer()->id)
            ->with(['items.product', 'items.variant', 'coupon', 'paymentGateway'])
            ->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => __('Order not found.')], 404);
        }

        return response()->json(['order' => $this->transformDetail($order)]);
    }

    /** POST /orders/{uuid}/reorder */
    public function reorder(string $uuid): JsonResponse
    {
        $order = Order::where('uuid', $uuid)
            ->where('customer_id', $this->customer()->id)
            ->with('items')
            ->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => __('Order not found.')], 404);
        }

        $cart = session('storefront_cart', []);

        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            $key = $item->product_variant_id ? 'v_'.$item->product_variant_id : 'p_'.$item->product_id;

            if (isset($cart[$key])) {
                $cart[$key]['qty'] += max(1, (int) $item->qty);
            } else {
                $cart[$key] = [
                    'product_id' => $item->product_id,
                    'variant_id' => $item->product_variant_id ?: null,
                    'qty' => max(1, (int) $item->qty),
                ];
            }
        }

        session(['storefront_cart' => $cart]);

        return response()->json(['success' => true, 'cart_count' => collect($cart)->sum('qty')]);
    }

    /**
     * POST /orders/{uuid}/cancel  {reason, note?}
     *
     * Cancels through OrderCancellationService (policy, stock restore, refund, notifications).
     * A blocked cancellation answers 422 with the policy message (OrderActionException).
     */
    public function cancel(CancelOrderRequest $request, string $uuid, OrderCancellationService $service): JsonResponse
    {
        $order = Order::where('uuid', $uuid)
            ->where('customer_id', $this->customer()->id)
            ->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => __('Order not found.')], 404);
        }

        $order = $service->cancel(
            $order,
            CancellationActor::Customer,
            $this->customer()->id,
            $request->cancellationReason(),
            $request->cancellationNote(),
        );

        $order->load(['items.product', 'items.variant', 'coupon', 'paymentGateway']);

        return response()->json([
            'success' => true,
            'message' => __('Order cancelled successfully.'),
            'order' => $this->transformDetail($order),
        ]);
    }

    /** GET /orders/cancellation-reasons — the reasons a customer can pick. */
    public function cancellationReasons(): JsonResponse
    {
        return response()->json([
            'data' => array_map(fn (CancellationReason $reason) => [
                'value' => $reason->value,
                'label' => $reason->label(),
                'requires_note' => $reason->requiresNote(),
            ], CancellationReason::forCustomer()),
        ]);
    }

    /** @return array{reason: string|null, reason_label: string|null, note: string|null, cancelled_at: string|null, cancelled_by: string|null, cancelled_by_label: string|null}|null */
    private function transformCancellation(Order $order): ?array
    {
        if (! $order->isCancelled()) {
            return null;
        }

        return [
            'reason' => $order->cancellation_reason?->value,
            'reason_label' => $order->cancellation_reason?->label(),
            'note' => $order->cancellation_note,
            'cancelled_at' => $order->cancelled_at?->toIso8601String(),
            'cancelled_by' => $order->cancelled_by_type?->value,
            'cancelled_by_label' => $order->cancelled_by_type?->customerFacingLabel(),
        ];
    }

    private function customer(): Customer
    {
        /** @var Customer $customer */
        $customer = Auth::guard('storefront')->user();

        return $customer;
    }

    private function transformSummary(Order $order): array
    {
        return [
            'uuid' => $order->uuid,
            'status' => $order->status?->value,
            'paid' => (bool) $order->paid,
            'payment_method' => $order->payment_method,
            'grand_total' => $order->grand_total,
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }

    private function transformDetail(Order $order): array
    {
        $cancellation = app(OrderCancellationPolicy::class)->evaluate($order, CancellationActor::Customer, app(OrderPolicyService::class));

        return array_merge($this->transformSummary($order), [
            'can_cancel' => $cancellation->allowed,
            'cancel_blocked_reason' => $cancellation->allowed ? null : $cancellation->message,
            'can_request_return_instead' => $cancellation->suggestReturn,
            'cancellation' => $this->transformCancellation($order),
            'payment_state' => $order->paymentState()->value,
            'refunded_amount' => round((float) $order->refunded_amount, 2),
            'refunds' => $order->refundsQuery()->latest('id')->get()->map(fn (Refund $refund) => [
                'reference' => $refund->reference,
                'amount' => round((float) $refund->amount, 2),
                'currency' => $refund->currency,
                'status' => $refund->status->value,
                'status_label' => $refund->status->label(),
                'method' => $refund->refund_method->value,
                'method_label' => $refund->refund_method->label(),
                'reason' => $refund->reason,
                'rejection_reason' => $refund->status === RefundStatus::Rejected ? $refund->failure_reason : null,
                'requested_at' => $refund->requested_at?->toIso8601String(),
                'processed_at' => $refund->processed_at?->toIso8601String(),
            ])->values(),
            'subtotal' => $order->subtotal,
            'discount_amount' => $order->discount_amount,
            'tax_amount' => $order->tax_amount,
            'shipping_charge' => $order->shipping_charge,
            'shipping_address' => $order->shipping_address,
            'coupon_code' => $order->coupon?->code,
            'items' => $order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->translationValue('name'),
                'variant_id' => $item->product_variant_id,
                'qty' => $item->qty,
                'price' => $item->price,
                'sub_total' => $item->sub_total,
            ])->values(),
        ]);
    }
}
