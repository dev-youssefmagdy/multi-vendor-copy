<?php

namespace App\Livewire\Website;

use App\Jobs\ProvisionTenantJob;
use App\Models\Country;
use App\Models\Tenant;
use App\Models\TenantCountry;
use App\Models\Tenant\Language as TenantLanguage;
use App\Models\Tenant\Theme;
use Illuminate\Support\Facades\DB;
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

    // Languages/theme defaults are read from the tenant DB once, as soon as background provisioning is done.
    public bool $tenantDataLoaded = false;

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

        $this->loadTenantStepData($tenant);
    }

    /** `provision_status` is only set for tenants registered through the deferred flow; legacy tenants are ready. */
    public function isProvisioned(?Tenant $tenant = null): bool
    {
        $tenant ??= Tenant::query()->find($this->tenantId);

        return $tenant && ($tenant->provision_status ?? 'ready') === 'ready';
    }

    public function isCatalogSynced(?Tenant $tenant = null): bool
    {
        $tenant ??= Tenant::query()->find($this->tenantId);

        return $tenant && ($tenant->sync_status ?? 'ready') === 'ready';
    }

    /**
     * Languages/theme defaults live in the tenant database, which is built in the background after
     * registration. Step 1 (countries) is central-only and never waits for it.
     */
    protected function loadTenantStepData(Tenant $tenant): void
    {
        if ($this->tenantDataLoaded || !$this->isProvisioned($tenant)) {
            return;
        }

        tenancy()->initialize($tenant);

        try {
            $this->languageIds = TenantLanguage::query()
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();

            $this->activeThemeId = (string) (Theme::query()->where('is_active', true)->value('id') ?? '');
        } finally {
            tenancy()->end();
        }

        $this->tenantDataLoaded = true;
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
        if ($this->step < 4 && ($this->step < 2 || $this->isProvisioned())) {
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
        // `exists` per array item would run one query per country; check them all in one query instead.
        $this->validate([
            'countryIds' => ['required', 'array', 'min:1', function (string $attribute, array $value, \Closure $fail) {
                $ids = array_unique(array_map('intval', $value));

                if (Country::query()->whereIn('id', $ids)->count() !== count($ids)) {
                    $fail(__('validation.exists', ['attribute' => $attribute]));
                }
            }],
            'countryIds.*' => ['integer'],
        ]);

        $tenant = Tenant::query()->find($this->tenantId);
        if (!$tenant) {
            return;
        }

        $now = now();
        $rows = array_map(fn ($cid) => [
            'tenant_id' => $this->tenantId,
            'country_id' => $cid,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], array_values(array_unique(array_map('intval', $this->countryIds))));

        DB::transaction(function () use ($rows) {
            TenantCountry::query()->where('tenant_id', $this->tenantId)->delete();
            TenantCountry::query()->insert($rows);
        });

        Tenant::saveData($this->tenantId, [
            'country_ids' => array_map('intval', $this->countryIds),
        ]);

        $this->step = 2;
    }

    public function submitLanguages(): void
    {
        $tenant = Tenant::query()->find($this->tenantId);
        if (!$tenant || !$this->isProvisioned($tenant)) {
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
        if (!$tenant || !$this->isProvisioned($tenant)) {
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
        // Catalog sync may still be running; it keeps filling the tenant DB after the owner logs in.
        if (!$tenant || !$this->isProvisioned($tenant)) {
            return;
        }

        Tenant::saveData($this->tenantId, ['launch_ready' => true]);

        $this->redirectToDashboard($tenant);
    }

    /** Percentage of phase-2 catalog sections done (0–100), from the last section the sync chain recorded. */
    protected function catalogProgress(?Tenant $tenant): int
    {
        if (!$tenant || $this->isCatalogSynced($tenant)) {
            return 100;
        }

        $sections = ProvisionTenantJob::catalogSections();
        $done = array_search($tenant->sync_completed_section ?? null, $sections, true);

        return $done === false ? 0 : (int) floor(($done + 1) / (count($sections) + 1) * 100);
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
        $provisioned = $this->isProvisioned($tenant);

        if ($tenant && $provisioned) {
            $this->loadTenantStepData($tenant);

            if (in_array($this->step, [2, 3], true)) {
                tenancy()->initialize($tenant);

                try {
                    $themes = $this->step === 3 ? Theme::query()->get() : $themes;
                    $availableLanguages = $this->step === 2 ? TenantLanguage::query()->orderBy('name')->get() : $availableLanguages;
                } finally {
                    tenancy()->end();
                }
            }
        }

        return view('livewire.website.store-onboarding-wizard', [
            'allCountries' => $allCountries,
            'themes' => $themes,
            'availableLanguages' => $availableLanguages,
            'provisioned' => $provisioned,
            'provisionFailed' => $tenant && ($tenant->provision_status ?? 'ready') === 'failed',
            'catalogSynced' => $this->isCatalogSynced($tenant),
            'catalogFailed' => $tenant && ($tenant->sync_status ?? 'ready') === 'failed',
            'catalogProgress' => $this->catalogProgress($tenant),
        ])->layout('layouts.website', ['title' => __('Set Up Your Store') . ' — Ecommet']);
    }
}
