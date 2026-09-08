<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Customer;
use App\Services\Tenant\Checkout\PlacesOrders;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    /** POST /checkout — places order(s) from the current session cart. */
    public function store(Request $request, PlacesOrders $placesOrders): JsonResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('storefront')->user();

        $validated = $request->validate([
            'payment_method' => 'required|string',
            'payment_gateway_id' => 'nullable|integer',
            'shipping.name' => 'required|string|max:120',
            'shipping.email' => 'required|email|max:120',
            'shipping.phone' => 'required|string|max:30',
            'shipping.address' => 'required|string|max:300',
            'shipping.address_id' => 'nullable|integer|exists:customer_addresses,id',
            'shipping.country_id' => 'nullable|integer',
            'coupon_code' => 'nullable|string|max:50',
        ]);

        $result = $placesOrders->place(
            $customer,
            [
                'name' => $validated['shipping']['name'],
                'email' => $validated['shipping']['email'],
                'phone' => $validated['shipping']['phone'],
                'address' => $validated['shipping']['address'],
                'address_id' => $validated['shipping']['address_id'] ?? null,
                'country_id' => $validated['shipping']['country_id'] ?? null,
            ],
            [
                'method' => $validated['payment_method'],
                'gateway_id' => $validated['payment_gateway_id'] ?? null,
            ],
            $validated['coupon_code'] ?? session('storefront_coupon'),
        );

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

        return response()->json([
            'success' => true,
            'stock_warnings' => $result['stock_warnings'],
            'requires_payment' => $result['requires_payment'],
            'payment_gateway_code' => $result['payment_gateway_code'],
            'order' => [
                'uuid' => $result['primary_order']?->uuid,
                'status' => $result['primary_order']?->status?->value,
                'grand_total' => $result['primary_order']?->grand_total,
            ],
            'companion_order' => $result['companion_order'] ? [
                'uuid' => $result['companion_order']->uuid,
                'status' => $result['companion_order']->status?->value,
                'grand_total' => $result['companion_order']->grand_total,
            ] : null,
        ], 201);
    }
}
