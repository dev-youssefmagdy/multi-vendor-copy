<?php

namespace App\Services\Tenant;

use App\Models\Tenant\CustomerAddress;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the "current" country for storefront pricing/shipping.
 *
 * Priority:
 *   1. An explicitly selected/entered address (e.g. the shipping address chosen in checkout).
 *   2. The logged-in customer's default (or latest) saved address.
 *   3. The visitor's session country (set by ApplyStorefrontSessionContext / LocaleSwitcher).
 */
class CustomerCountryResolver
{
    public function resolveId(?int $explicitAddressId = null, ?int $explicitCountryId = null): ?int
    {
        if ($explicitAddressId) {
            $address = $this->customerAddress($explicitAddressId);
            if ($address?->country_id) {
                return (int) $address->country_id;
            }
        }

        if ($explicitCountryId) {
            return (int) $explicitCountryId;
        }

        $defaultAddress = $this->defaultCustomerAddress();
        if ($defaultAddress?->country_id) {
            return (int) $defaultAddress->country_id;
        }

        $sessionCountryId = session('storefront_country_id');

        return $sessionCountryId ? (int) $sessionCountryId : null;
    }

    public function resolveIso2(?int $explicitAddressId = null, ?int $explicitCountryId = null): ?string
    {
        $countryId = $this->resolveId($explicitAddressId, $explicitCountryId);

        if (!$countryId) {
            return null;
        }

        return \App\Models\Country::query()->where('id', $countryId)->value('iso2');
    }

    private function customerAddress(int $addressId): ?CustomerAddress
    {
        $customer = Auth::guard('storefront')->user();

        return $customer?->addresses()->select(['id', 'country_id'])->find($addressId);
    }

    private function defaultCustomerAddress(): ?CustomerAddress
    {
        $customer = Auth::guard('storefront')->user();

        if (!$customer) {
            return null;
        }

        return $customer->addresses()->where('is_default', true)->first()
            ?? $customer->addresses()->latest()->first();
    }
}
