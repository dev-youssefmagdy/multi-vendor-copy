<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\PaymentGateway\PaymentManager;
use App\Services\Mobile\MobileCartPresenter;
use App\Services\Tenant\Checkout\PlacesOrders;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CheckoutController extends Controller
{
    public function __construct(private readonly MobileCartPresenter $presenter)
    {
    }

    /** GET /checkout/gateways : never exposes credentials. */
    public function gateways(): JsonResponse
    {
        $gateways = app(PaymentManager::class)->storefrontGateways()->map(fn ($g) => [
            'id' => $g['id'],
            'code' => $g['code'],
            'name' => $g['name'],
            'source' => $g['source'],
            'logo_url' => $g['logo_url'] ?? null,
            'payment_methods' => $g['payment_methods'] ?? [],
        ])->values()->all();

        return response()->json(['success' => true, 'data' => [
            'gateways' => $gateways,
            'cod' => ['code' => 'cod', 'gateway_id' => null],
        ]]);
    }

    /** POST /checkout/quote {shipping:{address_id?|country_id?}} */
    public function quote(Request $request): JsonResponse
    {
        $v = $request->validate([
            'shipping.address_id' => 'nullable|integer|exists:customer_addresses,id',
            'shipping.country_id' => 'nullable|integer',
        ]);

        return response()->json(['success' => true, 'data' => $this->presenter->build($request->user(), [
            'address_id' => $v['shipping']['address_id'] ?? null,
            'country_id' => $v['shipping']['country_id'] ?? null,
        ])]);
    }

    /**
     * POST /checkout : same service path as CheckoutPage::placeOrder (PlacesOrders -> OrderComposer -> OrderPricing).
     * Prices come from the server cart only. Optional `expected_total` (what the customer saw): a mismatch
     * with the freshly computed total answers 409 price_changed with the new quote and creates nothing.
     * Optional `Idempotency-Key` header replays the original response for a retried request.
     */
    public function store(Request $request, PlacesOrders $placesOrders): JsonResponse
    {
        $customer = $request->user();

        $validated = $request->validate([
            'payment_method' => 'required|string|max:50',
            'payment_gateway_id' => 'nullable|integer',
            'expected_total' => 'nullable|numeric|min:0',
            'shipping.name' => 'required|string|max:120',
            'shipping.email' => 'required|email|max:120',
            'shipping.phone' => 'required|string|max:30',
            'shipping.address' => 'required|string|max:300',
            'shipping.address_id' => 'nullable|integer|exists:customer_addresses,id',
            'shipping.country_id' => 'nullable|integer',
        ]);

        $idemKey = $request->header('Idempotency-Key');
        $cacheKey = $idemKey ? 'mobile_idem:'.tenant('id').':'.$customer->id.':'.sha1($idemKey) : null;
        if ($cacheKey && ($hit = Cache::get($cacheKey))) {
            return response()->json($hit, 200)->header('Idempotent-Replay', 'true');
        }

        $shipTo = ['address_id' => $validated['shipping']['address_id'] ?? null, 'country_id' => $validated['shipping']['country_id'] ?? null];
        $quote = $this->presenter->build($customer, $shipTo);

        if ($quote['items'] === []) {
            return response()->json(['success' => false, 'message' => __('Your cart is empty.'), 'code' => 'cart_empty'], 422);
        }
        if (isset($validated['expected_total']) && abs((float) $validated['expected_total'] - (float) $quote['grand_total']) > 0.01) {
            return response()->json(['success' => false, 'message' => __('Prices have changed. Please review your order.'), 'code' => 'price_changed', 'data' => $quote], 409);
        }

        $result = $placesOrders->place(
            $customer,
            $validated['shipping'] + ['address_id' => $shipTo['address_id'], 'country_id' => $shipTo['country_id']],
            ['method' => $validated['payment_method'], 'gateway_id' => $validated['payment_gateway_id'] ?? null],
            session('storefront_coupon'),
        );

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message'], 'code' => 'checkout_failed'], 422);
        }

        $primary = $result['primary_order'];
        $companion = $result['companion_order'];
        if ($companion) {
            Cache::put('mobile_companion:'.$primary->uuid, $companion->uuid, now()->addDay());
        }

        $payload = [
            'success' => true,
            'data' => [
                'stock_warnings' => $result['stock_warnings'],
                'requires_payment' => $result['requires_payment'],
                'payment_gateway_code' => $result['payment_gateway_code'],
                'order' => ['uuid' => $primary->uuid, 'status' => $primary->status?->value, 'grand_total' => $primary->grand_total],
                'companion_order' => $companion ? ['uuid' => $companion->uuid, 'status' => $companion->status?->value, 'grand_total' => $companion->grand_total] : null,
                'pay_endpoint' => $result['requires_payment'] ? '/api/mobile/v1/orders/'.$primary->uuid.'/pay' : null,
            ],
        ];

        if ($cacheKey) {
            Cache::put($cacheKey, $payload, now()->addMinutes(10));
        }

        return response()->json($payload, 201);
    }
}
