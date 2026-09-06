<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\Country;
use Livewire\Component;
use Livewire\WithPagination;

class CountriesPage extends Component
{
    use AuthorizesAdminPermissions;
    use InteractsWithAdminUi;
    use WithPagination;

    protected int $perPage = 20;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActiveForTenants(int $countryId): void
    {
        $this->authorizePermission('settings.countries.manage');

        $country = Country::query()->findOrFail($countryId);
        $country->update(['is_active_for_tenants' => !$country->is_active_for_tenants]);

        $this->toast('Country availability updated.');
    }

    public function toggleFree(int $countryId): void
    {
        $this->authorizePermission('settings.countries.manage');

        $country = Country::query()->findOrFail($countryId);
        $country->update(['is_free' => !$country->is_free]);

        $this->toast('Country pricing updated.');
    }

    public function render()
    {
        $countries = Country::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('iso2', 'like', "%{$this->search}%")
                        ->orWhere('iso3', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.admin.setting.countries-page', [
            'countries' => $countries,
            'canManageCountries' => $this->hasPermission('settings.countries.manage'),
        ]);
    }
}
