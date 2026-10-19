<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Pure order maths. The ONLY place the formulas in docs/ORDER_FORMULAS.md are implemented.
 * No models, no DB: callers pass plain numbers / arrays and persist what comes back.
 */
final class OrderPricing
{
    public const SOURCE_CENTRAL = 'central';
    public const SOURCE_VENDOR = 'vendor';

    /** Round half-up to cents, tolerant of float noise (20.215 -> 20.22). */
    public static function money(float $value): float
    {
        return round($value + ($value >= 0 ? 1e-9 : -1e-9), 2);
    }

    /**
     * price = cost + profit_amount (profit is "percentage" of cost or "fixed").
     * fixed_shipping is reported separately; it is charged once per order line.
     *
     * @return array{cost: float, profit_amount: float, price: float, fixed_shipping: float}
     */
    public static function unitPrice(float $cost, string $profitType, float $profitValue, float $fixedShipping = 0.0): array
    {
        $profit = $profitType === 'fixed'
            ? self::money($profitValue)
            : self::money($cost * $profitValue / 100);

        return [
            'cost' => self::money($cost),
            'profit_amount' => $profit,
            'price' => self::money($cost + $profit),
            'fixed_shipping' => self::money($fixedShipping),
        ];
    }

    /**
     * One order line.
     *
     * @param  string|null  $discountSource  'central'|'vendor' (who owns the flash sale), null when no discount
     * @return array{cost: float, profit_amount: float, qty: int, price: float, sub_total: float, fixed_shipping: float,
     *               discount: float, discount_source: ?string, tax: float, shipping_fee: float, grand_total: float}
     */
    public static function item(
        float $cost,
        float $profitAmount,
        int $qty,
        float $fixedShipping = 0.0,
        float $flashSalePercentage = 0.0,
        ?string $discountSource = null,
        float $tax = 0.0,
        float $shippingFee = 0.0,
    ): array {
        $qty = max(1, $qty);
        $price = self::money($cost + $profitAmount);
        $subTotal = self::money($price * $qty);
        $fixed = self::money($fixedShipping);
        $discount = $flashSalePercentage > 0
            ? self::money(($subTotal + $fixed) * $flashSalePercentage / 100)
            : 0.0;
        $tax = self::money($tax);
        $shippingFee = self::money($shippingFee);

        return [
            'cost' => self::money($cost),
            'profit_amount' => self::money($profitAmount),
            'qty' => $qty,
            'price' => $price,
            'sub_total' => $subTotal,
            'fixed_shipping' => $fixed,
            'discount' => $discount,
            'discount_source' => $discount > 0 ? $discountSource : null,
            'tax' => $tax,
            'shipping_fee' => $shippingFee,
            'grand_total' => self::money($subTotal + $fixed - $discount + $tax + $shippingFee),
        ];
    }

    /**
     * Order totals and the platform / vendor split.
     *
     * @param  array<int, array<string, mixed>>  $items  lines as returned by item()
     * @return array{items_grand_total: float, tax: float, coupon_discount: float, coupon_source: ?string,
     *               grand_total: float, platform_profit: float, vendor_profit: float}
     */
    public static function order(array $items, float $shippingCharge, float $couponDiscount = 0.0, ?string $couponSource = null): array
    {
        $itemsGrand = 0.0;
        $tax = 0.0;
        $platform = 0.0;
        $vendor = 0.0;

        foreach ($items as $item) {
            $qty = max(1, (int) $item['qty']);
            $discount = (float) ($item['discount'] ?? 0);
            $centralDiscount = ($item['discount_source'] ?? null) === self::SOURCE_CENTRAL ? $discount : 0.0;
            $vendorDiscount = ($item['discount_source'] ?? null) === self::SOURCE_VENDOR ? $discount : 0.0;

            $itemsGrand += (float) $item['grand_total'];
            $tax += (float) ($item['tax'] ?? 0);
            $platform += (float) $item['cost'] * $qty + (float) ($item['fixed_shipping'] ?? 0) - $centralDiscount;
            $vendor += (float) $item['profit_amount'] * $qty - $vendorDiscount;
        }

        $couponDiscount = $couponDiscount > 0 ? self::money($couponDiscount) : 0.0;
        $couponSource = $couponDiscount > 0 ? $couponSource : null;
        $shippingCharge = self::money($shippingCharge);

        $platform += $shippingCharge + $tax;
        if ($couponSource === self::SOURCE_CENTRAL) {
            $platform -= $couponDiscount;
        } elseif ($couponDiscount > 0) {
            // Vendor coupons, and coupons with an unknown owner, are borne by the vendor.
            $couponSource = self::SOURCE_VENDOR;
            $vendor -= $couponDiscount;
        }

        return [
            'items_grand_total' => self::money($itemsGrand),
            'tax' => self::money($tax),
            'coupon_discount' => $couponDiscount,
            'coupon_source' => $couponSource,
            'grand_total' => self::money($itemsGrand + $shippingCharge - $couponDiscount),
            'platform_profit' => self::money($platform),
            'vendor_profit' => self::money($vendor),
        ];
    }

    /** Coupon value for a cart: percentage of items_grand_total, or a fixed amount capped at it. */
    public static function couponDiscount(string $type, float $value, float $itemsGrandTotal): float
    {
        $discount = $type === 'percentage'
            ? $itemsGrandTotal * $value / 100
            : min($value, $itemsGrandTotal);

        return self::money(max(0.0, $discount));
    }
}
