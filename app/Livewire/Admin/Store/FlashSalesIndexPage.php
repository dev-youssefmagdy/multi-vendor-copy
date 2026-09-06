<?php

namespace App\Livewire\Admin\Store;

use App\Livewire\Admin\Base\AdminPage;
use App\Models\CentralFlashSale;
use App\Models\Country;

class FlashSalesIndexPage extends AdminPage
{
    public function mount(): void
    {
        $this->authorizePermission('store.flash-sales.manage');
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Flash Sales',
            'badge' => 'Store',
            'description' => 'Platform-wide flash sale campaigns, scoped per country and synced to tenant storefronts.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.store.flash-sales-index-page';
    }

    protected function pageData(): array
    {
        $counts = CentralFlashSale::query()
            ->selectRaw('country_id, count(*) as aggregate')
            ->groupBy('country_id')
            ->pluck('aggregate', 'country_id');

        $countries = Country::query()
            ->where('is_active_for_tenants', true)
            ->orderBy('name')
            ->get();

        return array_merge($this->pageMeta(), [
            'countries' => $countries,
            'defaultCount' => (int) ($counts[null] ?? 0),
            'countryCounts' => $counts,
        ]);
    }
}
