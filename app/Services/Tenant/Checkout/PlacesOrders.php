<?php

declare(strict_types=1);

namespace App\Services\Tenant\Checkout;

use App\Enums\DeliveryScope;
use App\Enums\OrderStatus;
use App\Enums\ShippingZoneStatus;
use App\Enums\Tenant\CouponType;
use App\Livewire\Tenant\Storefront\Concerns\ChecksCartStock;
use App\Models\Product as CentralProduct;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\PaymentGateway;
use App\Models\Tenant\Product as TenantProduct;
use App\Models\Tenant\ProductVariant as TenantProductVariant;
use App\PaymentGateway\PaymentManager;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Tenant\CustomerCountryResolver;
use App\Services\Tenant\OrderLifecycleService;
use Illuminate\Support\Str;

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

        // ── Partition cart items into own-product vs central-product groups ──────
        $ownItems = [];
        $centralItems = [];
        $ownProductCost = 0.0;
        $centralProductCost = 0.0;

        foreach ($cartItems as $item) {
            $orderItem = $this->buildOrderItemPayload(
                $item,
                $shippingCountryId,
                (float) ($shippingCalculation['line_weights'][$item['key']] ?? 0),
            );

            if (!$orderItem) {
                continue;
            }

            $product = $item['product'] ?? null;
            $variant = $item['variant'] ?? null;
            $ownerCost = $this->resolveOwnerCost($product, $variant, $shippingCountryId) * $orderItem['qty'];

            if ($product instanceof TenantProduct) {
                $pricing = $product->storefrontPricing($variant, $shippingCountryId);
                $flashSale = $pricing['flash_sale'] ?? null;

                if (($pricing['is_flash_sale'] ?? false) && $flashSale?->central_flash_sale_id !== null) {
                    $perUnitDiscount = (float) ($pricing['base_price'] ?? 0) - (float) ($pricing['current_price'] ?? 0);
                    $ownerCost = max(0.0, $ownerCost - ($perUnitDiscount * $orderItem['qty']));
                }
            }

            if ($product && ($product->is_own_product || $product->is_tenant_owned)) {
                $ownItems[] = $orderItem;
                $ownProductCost += $ownerCost;
            } else {
                $centralItems[] = $orderItem;
                $centralProductCost += $ownerCost;
            }
        }

        $allItems = array_merge($ownItems, $centralItems);

        if ($allItems === []) {
            $result['message'] = __('Your cart contains invalid items.');
            return $result;
        }

        $cartTotal = $repo->cartTotal();
        $appliedCoupon = null;
        $discountAmount = 0.0;
        $discountPercent = 0.0;

        if ($couponCode) {
            $appliedCoupon = Coupon::query()->where('code', $couponCode)->first();
        }

        if ($appliedCoupon) {
            $discountAmount = match ($appliedCoupon->type) {
                CouponType::Percentage => $cartTotal * (float) $appliedCoupon->value / 100,
                CouponType::Fixed => min((float) $appliedCoupon->value, $cartTotal),
            };
            $discountPercent = $cartTotal > 0 ? round($discountAmount / $cartTotal * 100, 4) : 0.0;
        }

        $cartFinalTotal = max(0.0, $cartTotal - $discountAmount);
        $gatewayId = $payment['gateway_id'] ?? null;
        $gateways = app(PaymentManager::class)->storefrontGateways();

        $selectedGateway = $gateways->where('code', $payment['method'])->firstWhere('id', $gatewayId);

        if ($gatewayId && !$selectedGateway) {
            $result['message'] = __('Selected payment gateway is no longer available. Please choose another payment method.');
            return $result;
        }

        $usingOwnGateway = $selectedGateway !== null && ($selectedGateway['source'] ?? '') === 'tenant';
        $usingCentralGateway = $selectedGateway !== null && !$usingOwnGateway && $gatewayId;

        $shippingAmount = (float) ($shippingCalculation['amount'] ?? 0);
        $centralCostAndShippingCost = round($centralProductCost + $shippingAmount, 2);
        $ownerProfit = !$usingOwnGateway
            ? round($centralCostAndShippingCost, 2)
            : round(max(0.0, $cartFinalTotal - $centralCostAndShippingCost), 2);

        $vendorGatewayId = null;
        $vendorGatewayFee = null;
        $vendorOwes = !$usingCentralGateway;

        if ($vendorOwes && $usingOwnGateway && $gatewayId) {
            $tenantGateway = PaymentGateway::find($gatewayId);
            if ($tenantGateway?->central_payment_gateway_id) {
                $centralGateway = \App\Models\PaymentGateway::find($tenantGateway->central_payment_gateway_id);
                if ($centralGateway) {
                    $vendorGatewayId = $centralGateway->id;
                    $vendorTotalDue = round($centralCostAndShippingCost, 2);
                    $vendorGatewayFee = $centralGateway->calculateFee($vendorTotalDue);
                }
            }
        }

        $shippingAddress = [
            'name' => $shipping['name'],
            'email' => $shipping['email'],
            'phone' => $shipping['phone'],
            'address' => $shipping['address'],
            'country' => $this->resolveShippingCountry($shippingAddressId, $shipping['address'], $customer),
            'country_id' => $shippingCountryId,
        ];

        $isMixedCart = $ownItems !== [] && $centralItems !== [];
        $orderGroupUuid = $isMixedCart ? (string) Str::uuid() : null;

        // ── Shipping is charged on the admin/central order only ────────────────
        $allSubtotal = array_sum(array_column($allItems, 'sub_total'));
        $ownSubtotal = array_sum(array_column($ownItems, 'sub_total'));
        $centralSubtotal = array_sum(array_column($centralItems, 'sub_total'));

        if ($isMixedCart) {
            $ownShipping = 0.0;
            $centralShipping = $shippingAmount;
        } else {
            $ownShipping = $shippingAmount;
            $centralShipping = $shippingAmount;
        }

        $lifecycle = app(OrderLifecycleService::class);

        // ── Helper to build and persist one order ─────────────────────────────
        $createOrder = function (array $orderItems, float $shippingCharge, float $ownerProfitForOrder, float $vendorCostForOrder) use ($customer, $selectedGateway, $gatewayId, $appliedCoupon, $discountPercent, $shippingCalculation, $vendorOwes, $vendorGatewayId, $vendorGatewayFee, $shippingAddress, $orderGroupUuid): Order {
            $order = Order::create([
                'uuid' => (string) Str::uuid(),
                'order_group_uuid' => $orderGroupUuid,
                'customer_id' => $customer->id,
                'status' => OrderStatus::Pending,
                'paid' => false,
                'payment_method' => $selectedGateway['code'] ?? 'cod',
                'payment_gateway_id' => $gatewayId ?: null,
                'discount_id' => $appliedCoupon?->id,
                'discount_percentage' => $discountPercent,
                'tax_percentage' => 0,
                'shipping_zone_rate_id' => $shippingCalculation['zone_rate_id'],
                'shipping_charge' => $shippingCharge,
                'owner_profit' => $ownerProfitForOrder,
                'vendor_cost' => $vendorOwes ? round($vendorCostForOrder, 2) : 0,
                'vendor_gateway_id' => $vendorOwes ? $vendorGatewayId : null,
                'vendor_gateway_fee' => $vendorOwes ? $vendorGatewayFee : 0,
                'shipping_address' => $shippingAddress,
            ]);

            foreach ($orderItems as $item) {
                OrderItem::create(array_merge($item, ['order_id' => $order->id]));
            }

            return $order;
        };

        if ($isMixedCart) {
            $centralOwnerProfit = !$usingOwnGateway
                ? round($centralProductCost + $centralShipping, 2)
                : round(max(0.0, $centralSubtotal - ($centralProductCost + $centralShipping)), 2);

            $ownOwnerProfit = !$usingOwnGateway
                ? round($ownShipping, 2)
                : round(max(0.0, $ownSubtotal - $ownShipping), 2);

            $centralOrder = $createOrder(
                $centralItems,
                $centralShipping,
                $centralOwnerProfit,
                $centralProductCost + $centralShipping,
            );

            $ownOrder = $createOrder(
                $ownItems,
                $ownShipping,
                $ownOwnerProfit,
                $ownShipping,
            );

            $lifecycle->recordPlaced($centralOrder->fresh(['items', 'customer', 'paymentGateway']));
            $lifecycle->recordPlaced($ownOrder->fresh(['items', 'customer', 'paymentGateway']));

            if ($appliedCoupon) {
                $lifecycle->recordCouponApplied($centralOrder->fresh(), $appliedCoupon->code, round($discountAmount * ($centralSubtotal / $allSubtotal), 2));
                $lifecycle->recordCouponApplied($ownOrder->fresh(), $appliedCoupon->code, round($discountAmount * ($ownSubtotal / $allSubtotal), 2));
            }

            $primaryOrder = $centralOrder;
            $companionOrder = $ownOrder;
        } else {
            $order = $createOrder($allItems, $shippingAmount, $ownerProfit, $centralCostAndShippingCost);

            $lifecycle->recordPlaced($order->fresh(['items', 'customer', 'paymentGateway']));

            if ($appliedCoupon) {
                $lifecycle->recordCouponApplied($order->fresh(), $appliedCoupon->code, round($discountAmount, 2));
            }

            $primaryOrder = $order;
            $companionOrder = null;
        }

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

    private function buildOrderItemPayload(array $cartItem, ?int $shippingCountryId, ?float $weight = null): ?array
    {
        $product = $cartItem['product'] ?? null;

        if (!$product instanceof TenantProduct) {
            return null;
        }

        $variant = $cartItem['variant'] ?? null;
        if (!$variant instanceof TenantProductVariant || (int) $variant->product_id !== (int) $product->id) {
            $variant = null;
        }

        $pricing = $product->storefrontPricing($variant, $shippingCountryId);
        $qty = max(1, (int) ($cartItem['qty'] ?? 1));
        $price = round((float) $pricing['current_price'], 2);

        $flashDiscount = $pricing['is_flash_sale']
            ? round(((float) $pricing['base_price'] - $price) * $qty, 2)
            : 0.0;

        return [
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'qty' => $qty,
            'price' => $price,
            'sub_total' => round($price * $qty, 2),
            'discount' => $flashDiscount,
            'tax' => 0,
            'weight' => round((float) ($weight ?? $this->resolveCartItemWeight($cartItem)), 3),
            'shipping_fee' => 0,
        ];
    }

    private function resolveOwnerCost(?TenantProduct $product, ?TenantProductVariant $variant, ?int $shippingCountryId): float
    {
        if (!$product) {
            return 0.0;
        }

        $fixedShippingCost = $product->fixedShippingCostForCountry($shippingCountryId ?? 0) ?? 0.0;

        if ($product->is_own_product) {
            return 0.0;
        }

        if ($product->is_tenant_owned) {
            return 0.0;
        }

        if ($variant && (int) $variant->product_id === (int) $product->id) {
            return (float) (($variant->real_price ?? 0) + $fixedShippingCost);
        }

        $centralProduct = $product->centralProduct;
        $centralPrice = $centralProduct?->sale_price ?: $centralProduct?->base_price;

        return (float) (($centralPrice ?? $product->default_price ?? 0) + $fixedShippingCost);
    }
}
