<?php

namespace App\Livewire\Website;

use App\Models\Country;
use App\Models\Tenant;
use App\Models\TenantCountry;
use App\Models\Tenant\Banner;
use App\Models\Tenant\Language as TenantLanguage;
use App\Models\Tenant\Theme;
use Livewire\Component;

class StoreOnboardingWizard extends Component
{
    // Steps: 1=countries  2=languages  3=theme  4=banners  5=done
    public int    $step      = 1;
    public string $tenantId  = '';
    public bool   $invalid   = false;

    // Step 1 — Countries (pre-filled from registration)
    public array $countryIds = [];

    // Step 2 — Languages
    public array $languageIds = [];

    // Step 3 — Active theme (global, not per-country)
    public string $activeThemeId = '';

    // Step 4 — Banner config per country
    // ['country_id' => ['title'=>'', 'subtitle'=>'', 'button_text'=>'', 'url'=>'']]
    public array $banners = [];

    public function mount(string $tenantId): void
    {
        $tenant = Tenant::query()->find($tenantId);

        if (!$tenant) {
            $this->invalid = true;
            return;
        }

        if ($tenant->isLaunchReady()) {
            $this->redirectToDashboard($tenant);
            return;
        }

        $this->tenantId = $tenantId;

        $this->countryIds = TenantCountry::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->pluck('country_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if (empty($this->countryIds)) {
            $this->countryIds = Country::query()
                ->where('is_active_for_tenants', true)
                ->where('is_free', true)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();
        }

        tenancy()->initialize($tenant);

        $this->languageIds = TenantLanguage::query()
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $this->activeThemeId = (string) (Theme::query()->where('is_active', true)->value('id') ?? '');

        foreach ($this->countryIds as $cid) {
            $this->banners[$cid] = [
                'title' => '',
                'subtitle' => '',
                'button_text' => '',
                'url' => '',
            ];
        }

        tenancy()->end();
    }

    public function nextStep(): void
    {
        match ($this->step) {
            1 => $this->submitCountries(),
            2 => $this->submitLanguages(),
            3 => $this->submitTheme(),
            4 => $this->submitBanners(),
            default => null,
        };
    }

    public function skipStep(): void
    {
        if ($this->step < 4) {
            $this->step++;
        }
    }

    public function submitCountries(): void
    {
        $this->validate([
            'countryIds' => ['required', 'array', 'min:1'],
            'countryIds.*' => ['integer', 'exists:countries,id'],
        ]);

        $tenant = Tenant::query()->find($this->tenantId);
        if (!$tenant) {
            return;
        }

        TenantCountry::query()->where('tenant_id', $this->tenantId)->delete();
        foreach ($this->countryIds as $cid) {
            TenantCountry::create([
                'tenant_id' => $this->tenantId,
                'country_id' => (int) $cid,
                'is_active' => true,
            ]);
        }

        Tenant::saveData($this->tenantId, [
            'country_ids' => array_map('intval', $this->countryIds),
        ]);

        foreach ($this->countryIds as $cid) {
            if (!isset($this->banners[$cid])) {
                $this->banners[$cid] = ['title' => '', 'subtitle' => '', 'button_text' => '', 'url' => ''];
            }
        }

        $this->step = 2;
    }

    public function submitLanguages(): void
    {
        $tenant = Tenant::query()->find($this->tenantId);
        if (!$tenant) {
            return;
        }

        if (!empty($this->languageIds)) {
            tenancy()->initialize($tenant);
            TenantLanguage::query()
                ->whereIn('id', array_map('intval', $this->languageIds))
                ->update(['is_active' => true]);
            tenancy()->end();
        }

        $this->step = 3;
    }

    public function submitTheme(): void
    {
        $tenant = Tenant::query()->find($this->tenantId);
        if (!$tenant) {
            return;
        }

        if (filled($this->activeThemeId)) {
            tenancy()->initialize($tenant);
            Theme::query()->update(['is_active' => false]);
            Theme::query()->whereKey((int) $this->activeThemeId)->update(['is_active' => true]);
            tenancy()->end();
        }

        $this->step = 4;
    }

    public function submitBanners(): void
    {
        $tenant = Tenant::query()->find($this->tenantId);
        if (!$tenant) {
            return;
        }

        tenancy()->initialize($tenant);

        foreach ($this->banners as $countryId => $bannerData) {
            if (blank($bannerData['title'] ?? '') && blank($bannerData['subtitle'] ?? '')) {
                continue;
            }

            $banner = new Banner();
            $banner->fill(['country_id' => (int) $countryId, 'url' => $bannerData['url'] ?? '']);
            $banner->save();

            $banner->syncTranslations([
                'en' => [
                    'title' => $bannerData['title'] ?? '',
                    'subtitle' => $bannerData['subtitle'] ?? '',
                    'button_text' => $bannerData['button_text'] ?? '',
                ],
            ]);
        }

        tenancy()->end();

        $this->step = 5;
    }

    public function launch(): void
    {
        $tenant = Tenant::query()->find($this->tenantId);
        if (!$tenant) {
            return;
        }

        Tenant::saveData($this->tenantId, ['launch_ready' => true]);

        $this->redirectToDashboard($tenant);
    }

    private function redirectToDashboard(Tenant $tenant): void
    {
        $domain = $tenant->domains()->first()?->domain ?? '';
        $this->redirect('http://' . $domain . '/admin/login');
    }

    public function render()
    {
        $tenant = Tenant::query()->find($this->tenantId);

        $allCountries = Country::query()
            ->where('is_active_for_tenants', true)
            ->orderByDesc('is_free')
            ->orderBy('name')
            ->get();

        $selectedCountries = $allCountries->whereIn('id', array_map('intval', $this->countryIds))->values();

        $themes = collect();
        $availableLanguages = collect();

        if ($tenant) {
            tenancy()->initialize($tenant);
            $themes = Theme::query()->get();
            $availableLanguages = TenantLanguage::query()->orderBy('name')->get();
            tenancy()->end();
        }

        return view('livewire.website.store-onboarding-wizard', [
            'allCountries' => $allCountries,
            'selectedCountries' => $selectedCountries,
            'themes' => $themes,
            'availableLanguages' => $availableLanguages,
        ])->layout('layouts.website', ['title' => __('Set Up Your Store') . ' — Ecommet']);
    }
}
