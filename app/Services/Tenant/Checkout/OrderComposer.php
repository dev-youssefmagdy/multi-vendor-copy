<?php

declare(strict_types=1);

namespace App\Services\Tenant\Checkout;

use App\Enums\OrderStatus;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\PaymentGateway;
use App\Models\Tenant\Product as TenantProduct;
use App\Models\Tenant\ProductVariant as TenantProductVariant;
use App\Services\Tenant\OrderLifecycleService;
use App\Support\OrderPricing;
use Illuminate\Support\Str;

/**
 * Single place that turns cart lines into priced order lines and persisted orders
 * (docs/ORDER_FORMULAS.md). Shared by PlacesOrders (API) and CheckoutPage (web);
 * all maths is delegated to OrderPricing.
 */
class OrderComposer
{
    /**
     * Priced order line for one cart entry (OrderPricing::item) plus an `is_own` flag.
     *
     * @return array<string, mixed>
     */
    public static function line(TenantProduct $product, ?TenantProductVariant $variant, int $qty, ?int $countryId): array
    {
        $pricing = $product->storefrontPricing($variant, $countryId);
        $isOwn = (bool) ($product->is_own_product || $product->is_tenant_owned);

        if ($isOwn) {
            // Vendor's own product: no platform cost, the whole price is vendor profit.
            $cost = 0.0;
            $profit = (float) $pricing['base_price'];
            $fixed = 0.0;
        } else {
            $source = $variant ?? $product;
            $breakdown = $source->priceBreakdownForCountry($countryId ?? ((int) session('storefront_country_id') ?: null));
            $cost = (float) $breakdown['cost'];
            $profit = (float) $breakdown['profit_amount'];
            $fixed = (float) $breakdown['fixed_shipping'];
        }

        $flashSale = $pricing['flash_sale'] ?? null;
        $source = $flashSale === null
            ? null
            : ($flashSale->central_flash_sale_id !== null ? OrderPricing::SOURCE_CENTRAL : OrderPricing::SOURCE_VENDOR);

        $line = OrderPricing::item(
            $cost,
            $profit,
            $qty,
            $fixed,
            (float) ($pricing['flash_sale_percentage'] ?? 0),
            $source,
        );
        $line['is_own'] = $isOwn;

        return $line;
    }

    /**
     * Create the order(s) for a cart. Mixed carts (own + central lines) yield two orders:
     * central (primary, carries shipping) and own (companion).
     *
     * @param  array<int, array<string, mixed>>  $cartItems  StorefrontRepository::cartItems() rows (with `line`)
     * @param  array<string, float>  $lineWeights  cart key => weight (kg)
     * @param  array<string, mixed>  $ctx  customer, coupon, gateway(array|null), gateway_id, shipping_amount,
     *                                     zone_rate_id, shipping_address
     * @return array{primary: Order, companion: ?Order}|null
     */
    public function create(array $cartItems, array $lineWeights, array $ctx): ?array
    {
        /** @var Customer $customer */
        $customer = $ctx['customer'];
        /** @var ?Coupon $coupon */
        $coupon = $ctx['coupon'] ?? null;
        $selectedGateway = $ctx['gateway'] ?? null;
        $gatewayId = $ctx['gateway_id'] ?? null;
        $shippingAmount = (float) ($ctx['shipping_amount'] ?? 0);

        $ownItems = [];
        $centralItems = [];

        foreach ($cartItems as $item) {
            $product = $item['product'] ?? null;
            if (!$product instanceof TenantProduct || !isset($item['line'])) {
                continue;
            }

            $line = $item['line'];
            $isOwn = (bool) $line['is_own'];
            unset($line['is_own']);

            $payload = array_merge($line, [
                'product_id' => $product->id,
                'product_variant_id' => $item['product_variant_id'] ?? ($item['variant']?->id),
                'weight' => round((float) ($lineWeights[$item['key']] ?? 0), 3),
            ]);

            if ($isOwn) {
                $ownItems[] = $payload;
            } else {
                $centralItems[] = $payload;
            }
        }

        if ($ownItems === [] && $centralItems === []) {
            return null;
        }

        $isMixed = $ownItems !== [] && $centralItems !== [];
        $groupUuid = $isMixed ? (string) Str::uuid() : null;

        $ownGrand = array_sum(array_column($ownItems, 'grand_total'));
        $centralGrand = array_sum(array_column($centralItems, 'grand_total'));
        $allGrand = $ownGrand + $centralGrand;

        $couponTotal = 0.0;
        $couponSource = null;
        $discountPercent = 0.0;
        if ($coupon) {
            $couponTotal = OrderPricing::couponDiscount((string) ($coupon->type->value ?? $coupon->type), (float) $coupon->value, (float) $allGrand);
            $couponSource = $coupon->central_coupon_id !== null ? OrderPricing::SOURCE_CENTRAL : OrderPricing::SOURCE_VENDOR;
            $discountPercent = $allGrand > 0 ? round($couponTotal / $allGrand * 100, 4) : 0.0;
        }

        $usingOwnGateway = $selectedGateway !== null && ($selectedGateway['source'] ?? '') === 'tenant';
        $usingCentralGateway = $selectedGateway !== null && !$usingOwnGateway && $gatewayId;
        $collectedBy = $usingCentralGateway ? 'platform' : 'vendor';

        $centralGateway = null;
        if ($collectedBy === 'vendor' && $usingOwnGateway && $gatewayId) {
            $tenantGateway = PaymentGateway::find($gatewayId);
            if ($tenantGateway?->central_payment_gateway_id) {
                $centralGateway = \App\Models\PaymentGateway::find($tenantGateway->central_payment_gateway_id);
            }
        }

        $lifecycle = app(OrderLifecycleService::class);

        $persist = function (array $items, float $shipping, float $coupon_discount) use (
            $customer, $coupon, $selectedGateway, $gatewayId, $discountPercent, $couponSource,
            $collectedBy, $centralGateway, $groupUuid, $ctx, $lifecycle
        ): Order {
            $totals = OrderPricing::order($items, $shipping, $coupon_discount, $couponSource);

            $order = Order::create([
                'uuid' => (string) Str::uuid(),
                'order_group_uuid' => $groupUuid,
                'customer_id' => $customer->id,
                'status' => OrderStatus::Pending,
                'paid' => false,
                'payment_method' => $selectedGateway['code'] ?? 'cod',
                'payment_gateway_id' => $gatewayId ?: null,
                'discount_id' => $coupon?->id,
                'discount_percentage' => $coupon ? $discountPercent : 0,
                'tax_percentage' => 0,
                'shipping_zone_rate_id' => $ctx['zone_rate_id'] ?? null,
                'shipping_charge' => $shipping,
                'coupon_discount' => $totals['coupon_discount'],
                'coupon_source' => $totals['coupon_source'],
                'platform_profit' => $totals['platform_profit'],
                'vendor_profit' => $totals['vendor_profit'],
                'collected_by' => $collectedBy,
                'vendor_gateway_id' => $centralGateway?->id,
                'vendor_gateway_fee' => $centralGateway ? $centralGateway->calculateFee(max(0.0, $totals['platform_profit'])) : 0,
                'shipping_address' => $ctx['shipping_address'],
            ]);

            foreach ($items as $item) {
                OrderItem::create(array_merge($item, ['order_id' => $order->id]));
            }

            $lifecycle->recordPlaced($order->fresh(['items', 'customer', 'paymentGateway']));
            if ($coupon && $totals['coupon_discount'] > 0) {
                $lifecycle->recordCouponApplied($order->fresh(), $coupon->code, $totals['coupon_discount']);
            }

            return $order;
        };

        if ($isMixed) {
            // Coupon split proportionally by items_grand_total; shipping stays on the central order.
            $centralCoupon = $allGrand > 0 ? OrderPricing::money($couponTotal * $centralGrand / $allGrand) : 0.0;
            $ownCoupon = OrderPricing::money($couponTotal - $centralCoupon);

            $central = $persist($centralItems, $shippingAmount, $centralCoupon);
            $own = $persist($ownItems, 0.0, $ownCoupon);

            return ['primary' => $central, 'companion' => $own];
        }

        return ['primary' => $persist(array_merge($ownItems, $centralItems), $shippingAmount, $couponTotal), 'companion' => null];
    }
}
