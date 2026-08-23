<?php

namespace App\Livewire\Tenant\Store;

use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Country;
use App\Models\TenantCountry;

class TargetCountriesPage extends TenantPage
{
    use InteractsWithTenantUi;

    public string $search = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Target Countries',
            'badge' => 'Storefront',
            'description' => 'Select the countries your store targets. Country-specific banners, flash sales, and badges are scoped to these.',
        ];
    }

    protected function pageData(): array
    {
        $tenantId = tenant()->id;

        $selectedIds = TenantCountry::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->pluck('country_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $countries = Country::query()
            ->where('is_active_for_tenants', true)
            ->orderByDesc('is_free')
            ->orderBy('name')
            ->get();

        if ($this->search !== '') {
            $search = mb_strtolower($this->search);
            $countries = $countries->filter(fn (Country $country) => str_contains(mb_strtolower((string) $country->name), $search)
                || str_contains(mb_strtolower((string) $country->iso2), $search))->values();
        }

        $domain = tenant()?->domains()->first()?->domain;
        $storefrontBase = $domain
            ? ((str_starts_with($domain, 'http') ? '' : 'https://') . $domain)
            : null;

        return array_merge(parent::pageData(), [
            'countries' => $countries,
            'selectedIds' => $selectedIds,
            'storefrontBase' => $storefrontBase,
            'selectedCount' => count($selectedIds),
            'totalCount' => Country::where('is_active_for_tenants', true)->count(),
        ]);
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.store.target-countries-page';
    }

    public function toggleCountry(int $countryId): void
    {
        $tenantId = tenant()->id;

        $existing = TenantCountry::query()
            ->where('tenant_id', $tenantId)
            ->where('country_id', $countryId)
            ->first();

        if ($existing) {
            $existing->update(['is_active' => !$existing->is_active]);
            $this->toast($existing->is_active ? 'Country added to targets.' : 'Country removed from targets.');
        } else {
            TenantCountry::create([
                'tenant_id' => $tenantId,
                'country_id' => $countryId,
                'is_active' => true,
            ]);
            $this->toast('Country added to targets.');
        }
    }

    public function selectAll(): void
    {
        $tenantId = tenant()->id;

        Country::where('is_active_for_tenants', true)->pluck('id')->each(function ($countryId) use ($tenantId) {
            TenantCountry::updateOrCreate(
                ['tenant_id' => $tenantId, 'country_id' => $countryId],
                ['is_active' => true],
            );
        });

        $this->toast('All countries selected.');
    }

    public function unselectAll(): void
    {
        TenantCountry::where('tenant_id', tenant()->id)->update(['is_active' => false]);

        $this->toast('All countries unselected.');
    }
}
