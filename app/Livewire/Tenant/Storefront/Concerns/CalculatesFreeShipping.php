<?php

namespace App\Livewire\Tenant\Storefront\Concerns;

use App\Enums\ShippingZoneStatus;
use App\Models\Branch;
use App\Models\ShippingZone;
use App\Models\Tenant\ProductVariant;
use App\Services\Tenant\CustomerCountryResolver;

trait CalculatesFreeShipping
{
    /**
     * Detect the free-shipping threshold (in grams) for the visitor's country.
     * Finds the active shipping zone for the detected country and returns the
     * min_weight of the first rate whose price is 0. When the country cannot be
     * detected, or it has no free-shipping rate configured, falls back to the
     * default branch's `default_free_shipping_weight` (set from Branch add/edit).
     */
    private function detectFreeShippingThreshold(): int
    {
        $countryId = app(CustomerCountryResolver::class)->resolveId();

        if ($countryId) {
            $zone = ShippingZone::query()
                ->where('status', ShippingZoneStatus::Active)
                ->where('country_id', $countryId)
                ->with(['rates' => fn($q) => $q->where('is_active', true)->orderBy('min_weight')])
                ->first();

            $freeRate = $zone?->rates->first(fn($r) => (float) $r->price === 0.0);

            if ($freeRate && $freeRate->min_weight !== null) {
                return (int) $freeRate->min_weight; // stored in grams
            }
        }

        return $this->defaultFreeShippingWeight();
    }

    /**
     * Fallback free-shipping weight threshold (grams) when the visitor's
     * country has no dedicated free-shipping rate — configured per branch,
     * defaults to 1500g (1.5kg) when no default branch exists.
     */
    private function defaultFreeShippingWeight(): int
    {
        $branch = Branch::query()->where('is_default', true)->first()
            ?? Branch::query()->first();

        return (int) ($branch->default_free_shipping_weight ?? 1500);
    }

    /**
     * Weight of a single cart item in kg — mirrors CheckoutPage::resolveCartItemWeight().
     */
    private function cartItemWeightKg(array $cartItem): float
    {
        $product = $cartItem['product'] ?? null;
        $variant = $cartItem['variant'] ?? null;
        $qty = max(1, (int) ($cartItem['qty'] ?? 1));

        $variantWeight = ($variant instanceof ProductVariant)
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

    /**
     * Total weight (in grams) of the items currently in the visitor's cart.
     */
    private function cartWeightGrams(array $cartItems): int
    {
        $cartWeightKg = (float) collect($cartItems)->sum(
            fn(array $item) => $this->cartItemWeightKg($item)
        );

        return (int) round($cartWeightKg * 1000);
    }
}
