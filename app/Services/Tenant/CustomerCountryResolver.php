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

    /** Request attribute holding the defaultCustomerAddress() memo. */
    public const MEMO_ATTRIBUTE = '_customer_country_default_address_memo';

    private function defaultCustomerAddress(): ?CustomerAddress
    {
        $customer = Auth::guard('storefront')->user();

        if (!$customer) {
            return null;
        }

        // resolveId() runs several times per page render; memoize the address
        // lookup for read-only (GET/HEAD) requests only, so a request that adds or
        // changes an address (checkout / Livewire POST) always sees the fresh row.
        $request = request();
        if (!$request->isMethodSafe()) {
            return $this->queryDefaultCustomerAddress($customer);
        }

        $memoKey = get_class($customer) . ':' . $customer->getAuthIdentifier();
        $memo = $request->attributes->get(self::MEMO_ATTRIBUTE, []);

        if (!array_key_exists($memoKey, $memo)) {
            $memo[$memoKey] = $this->queryDefaultCustomerAddress($customer);
            $request->attributes->set(self::MEMO_ATTRIBUTE, $memo);
        }

        return $memo[$memoKey];
    }

    private function queryDefaultCustomerAddress($customer): ?CustomerAddress
    {
        return $customer->addresses()->where('is_default', true)->first()
            ?? $customer->addresses()->latest()->first();
    }
}
