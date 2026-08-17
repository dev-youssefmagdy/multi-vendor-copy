<?php

namespace App\Livewire\Tenant\Store;

use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Country;
use App\Models\HomeVariant;
use App\Models\Tenant\TenantHomeVariant;
use App\Repositories\Tenant\TenantPanelRepository;

class HomeVariantsPage extends TenantPage
{
    use InteractsWithTenantUi;

    public ?int $selectedThemeId = null;

    public function mount(): void
    {
        $themes = app(TenantPanelRepository::class)->themes();

        $this->selectedThemeId = $themes->firstWhere('is_active', true)?->id
            ?? $themes->first()?->id;
    }

    public function selectTheme(int $themeId): void
    {
        $this->selectedThemeId = $themeId;
    }

    public function selectVariant(?int $countryId, ?int $homeVariantId): void
    {
        if (!$this->selectedThemeId) {
            return;
        }

        if (!$homeVariantId) {
            TenantHomeVariant::query()
                ->where('theme_id', $this->selectedThemeId)
                ->where('country_id', $countryId)
                ->delete();

            $this->toast('Reverted to the theme\'s default variant.');
            return;
        }

        TenantHomeVariant::query()->updateOrCreate(
            ['theme_id' => $this->selectedThemeId, 'country_id' => $countryId],
            ['home_variant_id' => $homeVariantId]
        );

        $this->toast('Home page variant saved.');
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.store.home-variants-page';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Home Variants',
            'badge' => 'Storefront',
            'description' => 'Pick which home page layout and color palette to use, optionally per country.',
        ];
    }

    protected function pageData(): array
    {
        $themes = app(TenantPanelRepository::class)->themes();
        $selectedTheme = $themes->firstWhere('id', $this->selectedThemeId);

        $rows = [];
        $availableVariants = collect();

        if ($selectedTheme) {
            $availableVariants = tenancy()->central(fn() => HomeVariant::query()
                ->forTheme($selectedTheme->slug)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get()
            );

            $selections = TenantHomeVariant::query()
                ->where('theme_id', $selectedTheme->id)
                ->get()
                ->keyBy(fn($row) => $row->country_id ?? 0);

            $countries = Country::query()
                ->where('is_active_for_tenants', true)
                ->orderBy('name')
                ->get();

            $rowDefs = $countries->map(fn(Country $country) => [
                'country_id' => $country->id,
                'label' => trim(($country->flag_emoji ?? '') . ' ' . $country->name),
            ])->prepend(['country_id' => null, 'label' => 'Default (All Countries)']);

            foreach ($rowDefs as $def) {
                $selection = $selections->get($def['country_id'] ?? 0);
                $rows[] = [
                    'country_id' => $def['country_id'],
                    'label' => $def['label'],
                    'selected_variant_id' => $selection?->home_variant_id,
                ];
            }
        }

        return array_merge(parent::pageData(), [
            'themes' => $themes,
            'selectedTheme' => $selectedTheme,
            'availableVariants' => $availableVariants,
            'rows' => $rows,
        ]);
    }
}
