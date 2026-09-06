<?php

namespace App\Livewire\Tenant\Store;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\HomeVariant;
use App\Models\Tenant\Theme;
use App\Models\Tenant\ThemeCountry;
use App\Models\Tenant\TenantHomeVariant;
use App\Models\Tenant\TenantPageSection;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Support\Str;

class ThemesPage extends ListPage
{
    use InteractsWithTenantUi;

    public bool $showCountriesModal = false;
    public ?int $countriesThemeId = null;
    public string $countriesThemeName = '';
    public string $countriesSearch = '';
    /** @var array<int, int> IDs of countries currently enabled for the theme. */
    public array $enabledCountryIds = [];

    protected function pageView(): string
    {
        return 'livewire.tenant.store.themes-page';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Themes',
            'badge' => 'Storefront',
            'description' => 'Switch the active storefront theme for the current tenant.',
        ];
    }

    protected function buildPreviewUrl(string $themeSlug, int $themeId, ?string $variantKey): string
    {
        $query = ['theme' => $themeSlug];

        if ($variantKey !== null) {
            $query['homepage_variant'] = $variantKey;
        }

        // Encode the tenant's current Page Builder section order for this theme.
        // The preview tenant has its own isolated DB so we pass it as a URL param.
        $sectionKeys = TenantPageSection::query()
            ->where('theme_id', $themeId)
            ->where('page', 'home')
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->pluck('section_key')
            ->all();

        if (!empty($sectionKeys)) {
            $query['sections'] = implode(',', $sectionKeys);
        }

        $centralDomain = config('tenancy.central_domains.0')
            ?: (parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost');
        $scheme = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_SCHEME) ?: 'http';

        return $scheme . '://' . $centralDomain . '/preview?' . http_build_query($query);
    }

    protected function pageData(): array
    {
        $themes = app(TenantPanelRepository::class)->themes();

        // Load all HomeVariants for all tenant themes from central DB
        $slugs = $themes->pluck('slug')->map(fn($s) => strtolower($s))->all();
        $allVariants = tenancy()->central(fn() => HomeVariant::query()
            ->whereIn('theme_slug', $slugs)
            ->where('is_active', true)
            ->orderBy('theme_slug')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
        );

        // Find the currently active universal variant:
        // The active universal Theme is the one with is_universal=true and is_active=true.
        // The active variant is the TenantHomeVariant with country_id=null for that theme.
        $activeUniversalTheme = $themes->first(fn(Theme $t) => $t->is_universal && $t->is_active);
        $activeVariantId = null;
        if ($activeUniversalTheme) {
            $activeVariantId = TenantHomeVariant::query()
                ->where('theme_id', $activeUniversalTheme->id)
                ->whereNull('country_id')
                ->value('home_variant_id');
        }

        $variantCards = $allVariants->map(function (HomeVariant $variant) use ($themes, $activeVariantId) {
            $theme = $themes->first(fn(Theme $t) => strtolower($t->slug) === strtolower($variant->theme_slug));
            if (!$theme) {
                return null;
            }

            $isUniversal = (bool) $theme->is_universal;
            $enabledCount = $theme->countries->where('is_enabled', true)->count();
            $totalCount = $theme->countries->count();

            // A variant is "active" when:
            // - Universal theme: this variant is the TenantHomeVariant default (country_id=null)
            // - Country-specific theme: theme is_active=true (variant concept doesn't apply)
            $isActive = $isUniversal
                ? ($activeVariantId !== null && (int) $activeVariantId === (int) $variant->id)
                : (bool) $theme->is_active;

            // Action logic mirrors existing theme page behaviour exactly:
            // Universal variant: radio — "Set Active" / "Active" (disabled)
            // Country-specific variant: toggle — "Activate" / "Deactivate"
            if ($isUniversal) {
                $actionLabel = $isActive ? 'Active' : 'Set Active';
                $actionMethod = $isActive ? null : 'activateVariant';
                $actionClass = $isActive
                    ? 'theme-pill-btn is-disabled'
                    : 'theme-pill-btn is-primary';
            } else {
                $actionLabel = $isActive ? 'Deactivate' : 'Activate';
                $actionMethod = $isActive ? 'deactivateTheme' : 'activateTheme';
                $actionClass = $isActive
                    ? 'theme-pill-btn is-danger'
                    : 'theme-pill-btn is-primary';
            }

            // Always build a live preview URL pointing at the preview tenant.
            // theme + homepage_variant + sections order are encoded as query params.
            $variantKey = tenancy()->central(fn() => $variant->key ?? null);
            $previewUrl = $this->buildPreviewUrl(strtolower($theme->slug), $theme->id, $variantKey ?: null);

            $domain = tenant()?->domains()->first()?->domain;
            $storefrontUrl = $isActive && $domain
                ? ((str_starts_with($domain, 'http') ? '' : 'https://') . $domain)
                : null;

            return [
                'theme_id' => $theme->id,
                'variant_id' => $variant->id,
                'name' => $variant->name,
                'description' => $variant->description ?? $theme->slug,
                'theme_name' => $theme->name ?? $theme->slug,
                'theme_slug' => $theme->slug,
                'is_active' => $isActive,
                'is_universal' => $isUniversal,
                'scope_label' => $isUniversal ? 'Universal' : 'Country-specific',
                'action_label' => $actionLabel,
                'action_method' => $actionMethod,
                'action_class' => $actionClass,
                'preview_path' => $previewUrl,
                'preview_label' => 'Preview',
                'storefront_url' => $storefrontUrl,
                'initials' => Str::upper(Str::substr((string) $variant->name, 0, 2)),
                'countries_label' => $isUniversal
                    ? 'All countries'
                    : sprintf('%d of %d countries', $enabledCount, $totalCount),
                'has_countries' => $totalCount > 0,
            ];
        })->filter()->values()->all();

        $activeVariantName = $allVariants->firstWhere('id', $activeVariantId)?->name
            ?? ($activeUniversalTheme?->name ?? 'No theme active');

        return array_merge(parent::pageData(), [
            'activeThemeName' => $activeVariantName,
            'variantCards' => $variantCards,
            'statistics' => [
                ['label' => 'Variants', 'value' => number_format(count($variantCards)), 'caption' => 'Available home page layouts', 'dot' => 'dot-cyan'],
                ['label' => 'Active', 'value' => number_format(collect($variantCards)->where('is_active', true)->count()), 'caption' => 'Currently live variant', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Themes', 'value' => number_format($themes->count()), 'caption' => 'Base themes available', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
        ]);
    }

    public function activateTheme(int $themeId, TenantPanelService $service): void
    {
        $service->activateTheme(Theme::query()->findOrFail($themeId));
        $this->toast('Theme activated successfully.');
        $this->dispatch('setup-step-completed');
    }

    public function activateVariant(int $themeId, int $variantId, TenantPanelService $service): void
    {
        $theme = Theme::query()->findOrFail($themeId);

        // Activate the theme itself (same as before — ensures is_active=true,
        // deactivates other universal themes)
        $service->activateTheme($theme);

        // Set this variant as the universal (all-countries) TenantHomeVariant
        TenantHomeVariant::query()->updateOrCreate(
            ['theme_id' => $themeId, 'country_id' => null],
            ['home_variant_id' => $variantId]
        );

        $this->toast('Variant activated successfully.');
        $this->dispatch('setup-step-completed');
    }

    public function deactivateTheme(int $themeId, TenantPanelService $service): void
    {
        try {
            $service->deactivateTheme(Theme::query()->findOrFail($themeId));
        } catch (\DomainException $e) {
            $this->toast($e->getMessage(), 'error');
            return;
        }
        $this->toast('Theme deactivated.');
    }

    public function openCountries(int $themeId): void
    {
        $theme = Theme::query()->with('countries')->findOrFail($themeId);

        $this->countriesThemeId = $theme->id;
        $this->countriesThemeName = (string) $theme->name;
        $this->enabledCountryIds = $theme->countries
            ->where('is_enabled', true)
            ->pluck('country_id')
            ->map(fn($v) => (int) $v)
            ->all();
        $this->countriesSearch = '';
        $this->showCountriesModal = true;
    }

    public function closeCountries(): void
    {
        $this->showCountriesModal = false;
        $this->countriesThemeId = null;
        $this->enabledCountryIds = [];
        $this->countriesSearch = '';
    }

    public function toggleCountry(int $countryId): void
    {
        $countryId = (int) $countryId;
        if (in_array($countryId, $this->enabledCountryIds, true)) {
            $this->enabledCountryIds = array_values(array_diff($this->enabledCountryIds, [$countryId]));
        } else {
            $this->enabledCountryIds[] = $countryId;
        }
    }

    public function saveCountries(): void
    {
        if (!$this->countriesThemeId) {
            return;
        }

        $theme = Theme::query()->with('countries')->findOrFail($this->countriesThemeId);
        $allowedIds = $theme->countries->pluck('country_id')->map(fn($v) => (int) $v)->all();
        $enabled = array_intersect($this->enabledCountryIds, $allowedIds);

        ThemeCountry::query()
            ->where('theme_id', $theme->id)
            ->update(['is_enabled' => false]);

        if (!empty($enabled)) {
            ThemeCountry::query()
                ->where('theme_id', $theme->id)
                ->whereIn('country_id', $enabled)
                ->update(['is_enabled' => true]);
        }

        $this->closeCountries();
        $this->toast('Theme countries updated successfully.');
    }

    public function getModalCountriesProperty()
    {
        if (!$this->showCountriesModal || !$this->countriesThemeId) {
            return collect();
        }

        $query = ThemeCountry::query()
            ->where('theme_id', $this->countriesThemeId)
            ->orderBy('name');

        if (trim($this->countriesSearch) !== '') {
            $search = '%' . trim($this->countriesSearch) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)->orWhere('iso2', 'like', $search);
            });
        }

        return $query->get();
    }

    public function clearFilters(): void
    {
    }
}
