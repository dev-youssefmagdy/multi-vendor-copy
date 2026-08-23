<?php

namespace App\Livewire\Tenant\Store;

use App\Livewire\Tenant\Base\TenantPage;
use App\Models\Country;
use App\Models\Tenant\FlashSale;
use App\Models\TenantCountry;

class FlashSalesIndexPage extends TenantPage
{
    protected function pageMeta(): array
    {
        return [
            'title' => 'Flash Sales',
            'badge' => 'Storefront',
            'description' => 'Manage limited-time discount campaigns per country, or set default campaigns shown everywhere.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.store.flash-sales-index-page';
    }

    protected function pageData(): array
    {
        $counts = FlashSale::query()
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
