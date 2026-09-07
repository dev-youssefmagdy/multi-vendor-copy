<?php

namespace App\Livewire\Tenant\Store;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Theme;
use App\Models\Tenant\ThemeCountry;
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

    protected function pageData(): array
    {
        $themes = app(TenantPanelRepository::class)->themes();
        $activeUniversal = $themes->first(fn(Theme $t) => $t->is_universal && $t->is_active);

        return array_merge(parent::pageData(), [
            'activeThemeName' => $activeUniversal?->name ?? 'No universal theme active',
            'themes' => $themes->map(function (Theme $theme) {
                $previewPath = $theme->preview_path ? trim((string) $theme->preview_path) : null;
                $isActive = (bool) $theme->is_active;
                $isUniversal = (bool) $theme->is_universal;
                $enabledCount = $theme->countries->where('is_enabled', true)->count();
                $totalCount = $theme->countries->count();

                // Activation UX differs between the two theme flavours:
                //  - Universal: "Set Active" radio — clicking swaps the primary theme.
                //  - Country-specific: "Activate" / "Deactivate" independent toggle.
                if ($isUniversal) {
                    $actionLabel = $isActive ? 'Active' : 'Set Active';
                    $actionMethod = $isActive ? null : 'activateTheme';
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

                return [
                    'id' => $theme->id,
                    'name' => $theme->name ?? 'Theme #' . $theme->id,
                    'slug' => $theme->slug ?? 'theme-' . $theme->id,
                    'is_active' => $isActive,
                    'is_universal' => $isUniversal,
                    'scope_label' => $isUniversal ? 'Universal' : 'Country-specific',
                    'action_label' => $actionLabel,
                    'action_method' => $actionMethod,
                    'action_class' => $actionClass,
                    'preview_path' => $previewPath,
                    'preview_label' => $previewPath ? 'Preview' : 'No Preview',
                    'initials' => Str::upper(Str::substr((string) ($theme->name ?? 'TH'), 0, 2)),
                    'countries_label' => $isUniversal
                        ? 'All countries'
                        : sprintf('%d of %d countries', $enabledCount, $totalCount),
                    'has_countries' => $totalCount > 0,
                ];
            })->all(),
            'statistics' => [
                ['label' => 'Themes', 'value' => number_format($themes->count()), 'caption' => 'Available tenant themes', 'dot' => 'dot-cyan'],
                ['label' => 'Active', 'value' => number_format($themes->where('is_active', true)->count()), 'caption' => 'Currently live storefront themes', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Universal', 'value' => number_format($themes->where('is_universal', true)->count()), 'caption' => 'Themes available in every country', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
        ]);
    }

    public function activateTheme(int $themeId, TenantPanelService $service): void
    {
        $service->activateTheme(Theme::query()->findOrFail($themeId));
        $this->toast('Theme activated successfully.');
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
