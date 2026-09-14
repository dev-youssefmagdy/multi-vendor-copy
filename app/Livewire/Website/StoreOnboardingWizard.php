<?php

namespace App\Livewire\Website;

use App\Models\Country;
use App\Models\Tenant;
use App\Models\TenantCountry;
use App\Models\Tenant\Language as TenantLanguage;
use App\Models\Tenant\Theme;
use Livewire\Component;

class StoreOnboardingWizard extends Component
{
    // Steps: 1=countries  2=languages  3=theme  4=done
    public int    $step      = 1;
    public string $tenantId  = '';
    public bool   $invalid   = false;

    // Step 1 — Countries (pre-filled from registration)
    public array $countryIds = [];

    // Step 2 — Languages
    public array $languageIds = [];

    // Step 3 — Active theme (global, not per-country)
    public string $activeThemeId = '';

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

        tenancy()->end();
    }

    public function nextStep(): void
    {
        match ($this->step) {
            1 => $this->submitCountries(),
            2 => $this->submitLanguages(),
            3 => $this->submitTheme(),
            default => null,
        };
    }

    public function skipStep(): void
    {
        if ($this->step < 4) {
            $this->step++;
        }
    }

    public function toggleAllCountries(bool $checked): void
    {
        $this->countryIds = $checked
            ? Country::query()
                ->where('is_active_for_tenants', true)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all()
            : [];
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
            'themes' => $themes,
            'availableLanguages' => $availableLanguages,
        ])->layout('layouts.website', ['title' => __('Set Up Your Store') . ' — Ecommet']);
    }
}
