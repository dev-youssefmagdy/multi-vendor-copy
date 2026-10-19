<?php

declare(strict_types=1);

namespace App\Services\Tenant\Checkout;

use App\Enums\DeliveryScope;
use App\Enums\ShippingZoneStatus;
use App\Livewire\Tenant\Storefront\Concerns\ChecksCartStock;
use App\Models\Product as CentralProduct;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product as TenantProduct;
use App\Models\Tenant\ProductVariant as TenantProductVariant;
use App\PaymentGateway\PaymentManager;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Tenant\CustomerCountryResolver;
use App\Services\Tenant\OrderLifecycleService;

/**
 * Builds validated order(s) from the current session cart, a shipping
 * address/contact, an optional coupon, and a payment method — exactly the
 * logic that used to live inline in CheckoutPage::placeOrder(). Extracted so
 * both the web checkout page and the storefront REST API create orders
 * through the same code path and can never diverge.
 *
 * This class does not perform HTTP-layer input validation (required/email/etc)
 * — callers validate their own input shape and pass already-clean values in.
 */
class PlacesOrders
{
    use ChecksCartStock;

    /**
     * @param  array{name:string,email:string,phone:string,address:string,address_id:?int,country_id:?int} $shipping
     * @param  array{method:?string,gateway_id:?int} $payment
     *
     * @return array{
     *     success: bool,
     *     message: ?string,
     *     stock_warnings: array<int, string>,
     *     requires_payment: bool,
     *     payment_gateway_code: ?string,
     *     primary_order: ?Order,
     *     companion_order: ?Order,
     * }
     */
    public function place(Customer $customer, array $shipping, array $payment, ?string $couponCode): array
    {
        $repo = app(StorefrontRepository::class);
        $cartItems = $repo->cartItems();

        $result = [
            'success' => false,
            'message' => null,
            'stock_warnings' => [],
            'requires_payment' => false,
            'payment_gateway_code' => null,
            'primary_order' => null,
            'companion_order' => null,
        ];

        if ($cartItems === []) {
            $result['message'] = __('Your cart is empty.');
            return $result;
        }

        $shippingAddressId = $shipping['address_id'] ?? null;
        $shippingCountryIdInline = $shipping['country_id'] ?? null;

        // ── Stock validation (warn only — OOS items no longer block submission) ──
        $stockWarnings = [];
        foreach ($cartItems as $item) {
            $product = $item['product'] ?? null;
            $variant = $item['variant'] ?? null;
            if (!$product) {
                continue;
            }
            $stockError = $this->checkProductStock($product, $variant, (int) $item['qty'], 0);
            if ($stockError !== null) {
                $stockWarnings[] = $stockError;
            }
        }
        $result['stock_warnings'] = $stockWarnings;
        // ─────────────────────────────────────────────────────────────────────

        $shippingCalculation = $this->resolveShippingCalculation($cartItems, $shippingAddressId, $shippingCountryIdInline, $customer);

        if (!$shippingCalculation['available']) {
            $result['message'] = $shippingCalculation['message'] ?: __('Shipping is not available for the selected address.');
            return $result;
        }

        $shippingCountryId = $this->resolveShippingCountryId($shippingAddressId, $shippingCountryIdInline);

        // Re-price the cart for the shipping country so persisted lines match what the customer saw.
        $cartItems = $repo->cartItems($shippingCountryId);

        $appliedCoupon = $couponCode
            ? Coupon::query()->where('code', $couponCode)->first()
            : null;

        $gatewayId = $payment['gateway_id'] ?? null;
        $gateways = app(PaymentManager::class)->storefrontGateways();

        $selectedGateway = $gateways->where('code', $payment['method'])->firstWhere('id', $gatewayId);

        if ($gatewayId && !$selectedGateway) {
            $result['message'] = __('Selected payment gateway is no longer available. Please choose another payment method.');
            return $result;
        }

        $shippingAddress = [
            'name' => $shipping['name'],
            'email' => $shipping['email'],
            'phone' => $shipping['phone'],
            'address' => $shipping['address'],
            'country' => $this->resolveShippingCountry($shippingAddressId, $shipping['address'], $customer),
            'country_id' => $shippingCountryId,
        ];

        $created = app(OrderComposer::class)->create(
            $cartItems,
            $shippingCalculation['line_weights'],
            [
                'customer' => $customer,
                'coupon' => $appliedCoupon,
                'gateway' => $selectedGateway,
                'gateway_id' => $gatewayId,
                'shipping_amount' => (float) ($shippingCalculation['amount'] ?? 0),
                'zone_rate_id' => $shippingCalculation['zone_rate_id'],
                'shipping_address' => $shippingAddress,
            ],
        );

        if ($created === null) {
            $result['message'] = __('Your cart contains invalid items.');
            return $result;
        }

        $lifecycle = app(OrderLifecycleService::class);
        $primaryOrder = $created['primary'];
        $companionOrder = $created['companion'];

        $result['success'] = true;
        $result['primary_order'] = $primaryOrder;
        $result['companion_order'] = $companionOrder;

        if ($gatewayId) {
            if ($companionOrder) {
                // Stash companion UUID so PaymentController can mark it paid alongside the primary
                session(['storefront_companion_order_uuid' => $companionOrder->uuid]);
            }
            $result['requires_payment'] = true;
            $result['payment_gateway_code'] = $selectedGateway['code'];

            return $result;
        }

        $lifecycle->recordCodConfirmed($primaryOrder->fresh());
        if ($companionOrder) {
            $lifecycle->recordCodConfirmed($companionOrder->fresh());
        }
        session()->forget(['storefront_cart', 'storefront_coupon']);

        return $result;
    }

    // ─── Private helpers (mirrors of the former CheckoutPage private methods) ──

    private function resolveShippingCalculation(array $cartItems, ?int $shippingAddressId, ?int $shippingCountryIdInline, Customer $customer): array
    {
        $lineWeights = collect($cartItems)
            ->mapWithKeys(fn(array $item) => [(string) ($item['key'] ?? uniqid('cart_', true)) => $this->resolveCartItemWeight($item)])
            ->all();

        $lineFees = collect($lineWeights)
            ->map(fn() => 0.0)
            ->all();

        $shippableItems = collect($cartItems)
            ->filter(function (array $item): bool {
                $centralProduct = $item['product']?->centralProduct;

                return $centralProduct instanceof CentralProduct
                    && (bool) $centralProduct->requires_shipping
                    && $centralProduct->delivery_scope !== DeliveryScope::Digital;
            })
            ->map(function (array $item) use ($lineWeights): array {
                $item['line_weight'] = (float) ($lineWeights[$item['key']] ?? 0);

                return $item;
            })
            ->values();

        if ($shippableItems->isEmpty()) {
            return [
                'available' => true,
                'amount' => 0.0,
                'zone_rate_id' => null,
                'zone_label' => null,
                'rate_label' => null,
                'message' => null,
                'line_fees' => $lineFees,
                'line_weights' => $lineWeights,
            ];
        }

        $countryId = $this->resolveShippingCountryId($shippingAddressId, $shippingCountryIdInline);

        if (!$countryId) {
            return [
                'available' => false,
                'amount' => 0.0,
                'zone_rate_id' => null,
                'zone_label' => null,
                'rate_label' => null,
                'message' => __('Please select a shipping country to calculate delivery charges.'),
                'line_fees' => $lineFees,
                'line_weights' => $lineWeights,
            ];
        }

        $zone = \App\Models\ShippingZone::query()
            ->where('status', ShippingZoneStatus::Active)
            ->where('country_id', $countryId)
            ->with(['rates' => fn($query) => $query->where('is_active', true)->orderBy('min_weight')->orderBy('max_weight')])
            ->first();

        if (!$zone) {
            return [
                'available' => false,
                'amount' => 0.0,
                'zone_rate_id' => null,
                'zone_label' => null,
                'rate_label' => null,
                'message' => __('Shipping is not available to your selected country.'),
                'line_fees' => $lineFees,
                'line_weights' => $lineWeights,
            ];
        }

        $totalWeight = round((float) $shippableItems->sum('line_weight'), 3);
        $rate = $zone->rates->first(fn($zoneRate) => $this->rateMatchesWeight($zoneRate, $totalWeight));

        if (!$rate) {
            return [
                'available' => true,
                'amount' => 0.0,
                'zone_rate_id' => null,
                'zone_label' => $zone->name,
                'rate_label' => __('Free'),
                'message' => null,
                'line_fees' => $lineFees,
                'line_weights' => $lineWeights,
            ];
        }

        $amount = round((float) $rate->price, 2);

        return [
            'available' => true,
            'amount' => $amount,
            'zone_rate_id' => $rate->id,
            'zone_label' => $zone->name,
            'rate_label' => $rate->name,
            'message' => null,
            'line_fees' => $this->allocateShippingFees($amount, $shippableItems, $lineFees),
            'line_weights' => $lineWeights,
        ];
    }

    private function rateMatchesWeight(object $rate, float $weight): bool
    {
        $grams = $weight * 1000;
        $minWeight = $rate->min_weight !== null ? (float) $rate->min_weight : null;
        $maxWeight = $rate->max_weight !== null ? (float) $rate->max_weight : null;

        return ($minWeight === null || $grams >= $minWeight)
            && ($maxWeight === null || $grams <= $maxWeight);
    }

    private function allocateShippingFees(float $amount, $shippableItems, array $lineFees): array
    {
        if ($amount <= 0 || $shippableItems->isEmpty()) {
            return $lineFees;
        }

        $metrics = $shippableItems
            ->mapWithKeys(fn(array $item) => [$item['key'] => max((float) ($item['line_weight'] ?? 0), 0)])
            ->all();

        $totalMetric = array_sum($metrics);

        if ($totalMetric <= 0) {
            $metrics = $shippableItems
                ->mapWithKeys(fn(array $item) => [$item['key'] => max(1, (int) ($item['qty'] ?? 1))])
                ->all();
            $totalMetric = array_sum($metrics);
        }

        $remaining = round($amount, 2);
        $lastKey = array_key_last($metrics);

        foreach ($metrics as $key => $metric) {
            if ($key === $lastKey) {
                $lineFees[$key] = round($remaining, 2);
                continue;
            }

            $allocated = round($amount * ($metric / $totalMetric), 2);
            $lineFees[$key] = $allocated;
            $remaining = round($remaining - $allocated, 2);
        }

        return $lineFees;
    }

    private function resolveShippingCountryId(?int $shippingAddressId, ?int $shippingCountryIdInline): ?int
    {
        return app(CustomerCountryResolver::class)->resolveId($shippingAddressId, $shippingCountryIdInline);
    }

    private function resolveShippingCountry(?int $shippingAddressId, string $shippingAddressText, Customer $customer): string
    {
        if ($shippingAddressId) {
            $address = $customer->addresses()->select(['id', 'country'])->find($shippingAddressId);

            if (filled($address?->country)) {
                return trim((string) $address->country);
            }
        }

        $segments = collect(explode(',', $shippingAddressText))
            ->map(fn(string $segment) => trim($segment))
            ->filter();

        return (string) ($segments->last() ?? '');
    }

    private function resolveCartItemWeight(array $cartItem): float
    {
        $product = $cartItem['product'] ?? null;
        $variant = $cartItem['variant'] ?? null;
        $qty = max(1, (int) ($cartItem['qty'] ?? 1));

        $variantWeight = ($variant instanceof TenantProductVariant)
            ? (int) ($variant->centralVariant?->weight_grams ?? 0)
            : 0;

        $weightGrams = $variantWeight > 0
            ? $variantWeight
            : (int) ($product?->centralProduct?->weight_grams ?? 0);

        if ($weightGrams <= 0) {
            return 0.0;
        }

        return round(($weightGrams / 1000) * $qty, 3);
    }
}
