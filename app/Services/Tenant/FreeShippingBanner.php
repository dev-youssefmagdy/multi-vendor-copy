<?php

namespace App\Services\Tenant;

use App\Enums\ShippingZoneStatus;
use App\Models\ShippingZone;

/**
 * Free-shipping threshold advertised on the storefront for the visitor's country.
 * Read-only display helper: it reads the same zone rates the cart/checkout use
 * (the first active rate with price 0 -> its min_weight, in grams) and never
 * changes any shipping charge.
 */
class FreeShippingBanner
{
    private ?array $resolved = null;

    /** Free-shipping weight threshold in grams, or null when the country has no free rate. */
    public function thresholdGrams(?int $countryId = null): ?int
    {
        $countryId ??= app(CustomerCountryResolver::class)->resolveId();

        if (! $countryId) {
            return null;
        }

        if ($this->resolved !== null && array_key_exists($countryId, $this->resolved)) {
            return $this->resolved[$countryId];
        }

        $zone = ShippingZone::query()
            ->where('status', ShippingZoneStatus::Active)
            ->where('country_id', $countryId)
            ->with(['rates' => fn ($q) => $q->where('is_active', true)->orderBy('min_weight')])
            ->first();

        $freeRate = $zone?->rates->first(fn ($rate) => (float) $rate->price === 0.0 && $rate->min_weight !== null);

        return $this->resolved[$countryId] = $freeRate ? (int) $freeRate->min_weight : null;
    }

    /** "1.2 kg" / "800 g" style label for a gram value. */
    public function weightLabel(int $grams): string
    {
        return $grams >= 1000
            ? rtrim(rtrim(number_format($grams / 1000, 2, '.', ''), '0'), '.') . ' ' . __('kg')
            : $grams . ' ' . __('g');
    }
}
