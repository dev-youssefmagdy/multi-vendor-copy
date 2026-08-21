<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\StorefrontRepository;
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
                $orders = $orders->filter(fn(Order $o) => $o->status === $statusEnum)->values();
            }
        }

        return response()->json(['data' => $orders->map(fn(Order $o) => $this->transformSummary($o))->values()]);
    }

    /** GET /orders/{uuid} */
    public function show(string $uuid): JsonResponse
    {
        $order = Order::where('uuid', $uuid)
            ->where('customer_id', $this->customer()->id)
            ->with(['items.product', 'items.variant', 'coupon', 'paymentGateway'])
            ->first();

        if (!$order) {
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

        if (!$order) {
            return response()->json(['success' => false, 'message' => __('Order not found.')], 404);
        }

        $cart = session('storefront_cart', []);

        foreach ($order->items as $item) {
            if (!$item->product_id) {
                continue;
            }

            $key = $item->product_variant_id ? 'v_' . $item->product_variant_id : 'p_' . $item->product_id;

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

    /** POST /orders/{uuid}/cancel */
    public function cancel(string $uuid): JsonResponse
    {
        $order = Order::where('uuid', $uuid)
            ->where('customer_id', $this->customer()->id)
            ->whereIn('status', [OrderStatus::Pending, OrderStatus::Processing])
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => __('Order not found or cannot be cancelled.')], 422);
        }

        $order->update(['status' => OrderStatus::Cancelled]);

        return response()->json(['success' => true]);
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
        return array_merge($this->transformSummary($order), [
            'subtotal' => $order->subtotal,
            'discount_amount' => $order->discount_amount,
            'tax_amount' => $order->tax_amount,
            'shipping_charge' => $order->shipping_charge,
            'shipping_address' => $order->shipping_address,
            'coupon_code' => $order->coupon?->code,
            'items' => $order->items->map(fn($item) => [
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
