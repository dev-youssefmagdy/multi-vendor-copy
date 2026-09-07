<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Base\AdminPage;
use App\Models\Country;
use App\Models\DefaultBanner;

class DefaultBannersIndexPage extends AdminPage
{
    public function mount(): void
    {
        $this->authorizePermission('settings.default-banners.manage');
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Default Banners',
            'badge' => 'Settings',
            'description' => 'Define default storefront banners that are automatically synced to every new tenant on registration, per country.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.setting.default-banners-index';
    }

    protected function pageData(): array
    {
        $counts = DefaultBanner::query()
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
