<?php

declare(strict_types=1);

namespace App\Services\Mobile;

use App\Livewire\Tenant\Storefront\Concerns\CalculatesFreeShipping;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\Customer;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Tenant\Checkout\PlacesOrders;
use App\Services\Tenant\CustomerCountryResolver;
use App\Support\OrderPricing;

/**
 * Cart / checkout-quote payload. Every number comes from the same services as the web checkout
 * (StorefrontRepository::cartItems -> OrderComposer::line, PlacesOrders shipping, OrderPricing);
 * nothing the client sends (prices, totals) is ever used.
 */
class MobileCartPresenter
{
    use CalculatesFreeShipping;

    /** Applied coupon if still valid for the customer's country, else null (and cleared from the session). */
    public function validCoupon(): ?Coupon
    {
        $code = session('storefront_coupon');
        if (!$code) {
            return null;
        }

        $coupon = Coupon::query()->where('code', $code)
            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
            ->first();

        if (!$coupon || !$coupon->appliesToCountry(app(CustomerCountryResolver::class)->resolveId())) {
            session()->forget('storefront_coupon');

            return null;
        }

        return $coupon;
    }

    /**
     * @param  array{address_id?:?int,country_id?:?int}|null  $shipTo  when given, shipping is quoted and totals include it
     * @return array<string, mixed>
     */
    public function build(?Customer $customer = null, ?array $shipTo = null): array
    {
        $repo = app(StorefrontRepository::class);
        $items = $repo->cartItems();
        $lines = array_map(function (array $i) {
            $line = $i['line'];
            unset($line['is_own']);

            return $line;
        }, $items);

        $itemsTotal = OrderPricing::money((float) array_sum(array_column($lines, 'grand_total')));
        $coupon = $this->validCoupon();
        $couponDiscount = 0.0;
        $couponSource = null;
        $couponError = null;

        if ($coupon) {
            if ($coupon->minimum_spend !== null && $itemsTotal < (float) $coupon->minimum_spend) {
                $couponError = 'minimum_spend_not_met';
            } else {
                $couponDiscount = OrderPricing::couponDiscount((string) ($coupon->type->value ?? $coupon->type), (float) $coupon->value, $itemsTotal);
                $couponSource = $coupon->central_coupon_id !== null ? OrderPricing::SOURCE_CENTRAL : OrderPricing::SOURCE_VENDOR;
            }
        }

        $shipping = null;
        $shippingAmount = 0.0;
        if ($shipTo !== null && $customer) {
            $calc = app(PlacesOrders::class)->resolveShippingCalculation($items, $shipTo['address_id'] ?? null, $shipTo['country_id'] ?? null, $customer);
            $shipping = [
                'available' => (bool) $calc['available'],
                'amount' => (float) $calc['amount'],
                'zone' => $calc['zone_label'],
                'rate' => $calc['rate_label'],
                'message' => $calc['message'],
            ];
            $shippingAmount = $calc['available'] ? (float) $calc['amount'] : 0.0;
        }

        $totals = OrderPricing::order($lines, $shippingAmount, $couponDiscount, $couponSource);

        $threshold = $this->detectFreeShippingThreshold();
        $weight = $this->cartWeightGrams($items);
        $currency = $repo->currentCurrency();

        return [
            'items' => array_map(fn (array $i) => [
                'key' => $i['key'],
                'product_id' => $i['product_id'],
                'variant_id' => $i['product_variant_id'],
                'slug' => $i['product']->slug,
                'name' => $i['product']->translationValue('name') ?? $i['product']->slug,
                'image' => $i['product']->primary_image_url,
                'qty' => $i['qty'],
                'unit_price' => $i['price'],
                'original_price' => $i['original_price'],
                'is_flash_sale' => (bool) $i['is_flash_sale'],
                'line_total' => OrderPricing::money((float) $i['line']['grand_total']),
                'out_of_stock' => (bool) $i['is_out_of_stock'],
            ], $items),
            'count' => (int) array_sum(array_column($items, 'qty')),
            'items_total' => $itemsTotal,
            'coupon' => $coupon ? [
                'code' => $coupon->code,
                'discount' => $totals['coupon_discount'],
                'valid' => $couponError === null,
                'error' => $couponError,
            ] : null,
            'shipping' => $shipping,
            'tax' => $totals['tax'],
            'grand_total' => $totals['grand_total'],
            'free_shipping' => [
                'threshold_grams' => $threshold,
                'cart_weight_grams' => $weight,
                'remaining_grams' => max(0, $threshold - $weight),
                'progress_pct' => $threshold > 0 ? min(100, (int) round($weight / $threshold * 100)) : 0,
            ],
            'currency' => ['code' => $currency?->code, 'symbol' => $currency?->symbol, 'rate' => (float) ($currency?->conversion_rate ?? 1)],
            'has_unavailable' => (bool) array_filter($items, fn ($i) => $i['is_out_of_stock']),
        ];
    }
}
