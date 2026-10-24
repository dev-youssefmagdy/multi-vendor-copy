<?php

namespace App\Livewire\Tenant\Store;

use App\Livewire\Tenant\Base\TenantPage;
use App\Models\Country;
use App\Models\Tenant\Coupon;
use App\Models\TenantCountry;

class CouponsIndexPage extends TenantPage
{
    protected function pageMeta(): array
    {
        return [
            'title' => 'Coupons',
            'badge' => 'Storefront',
            'description' => 'Manage discount codes per country, or set default coupons usable everywhere.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.store.coupons-index-page';
    }

    protected function pageData(): array
    {
        $counts = Coupon::query()
            ->selectRaw('country_id, count(*) as aggregate')
            ->groupBy('country_id')
            ->pluck('aggregate', 'country_id');

        $countryIds = TenantCountry::query()
            ->where('tenant_id', tenant()->id)
            ->where('is_active', true)
            ->pluck('country_id');

        $countries = Country::query()
            ->whereIn('id', $countryIds)
            ->orderBy('name')
            ->get();

        return array_merge(parent::pageData(), [
            'countries' => $countries,
            'defaultCount' => (int) ($counts[null] ?? 0),
            'countryCounts' => $counts,
        ]);
    }
}
