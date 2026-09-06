<?php

namespace App\Livewire\Tenant\Store;

use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\TenantPageSection;
use App\Models\Tenant\Theme;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\PageBuilder\SectionRegistry;

class PageBuilderPage extends TenantPage
{
    use InteractsWithTenantUi;

    // Only the Home page is wired up in this phase. When Category/Product/
    // Cart/Checkout are added, introduce a $page property (see
    // TenantPageSection::PAGES) and a page-type selector alongside the
    // theme switcher below.
    public const PAGE = 'home';

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

    public function updateOrder(array $orderedKeys): void
    {
        if (!$this->selectedThemeId) {
            return;
        }

        foreach ($orderedKeys as $index => $key) {
            TenantPageSection::query()->updateOrCreate(
                [
                    'theme_id' => $this->selectedThemeId,
                    'page' => self::PAGE,
                    'section_key' => $key,
                ],
                [
                    'sort_order' => $index,
                ]
            );
        }

        $this->toast('Section order saved.');
    }

    public function toggleVisibility(string $sectionKey): void
    {
        if (!$this->selectedThemeId) {
            return;
        }

        $theme = Theme::query()->find($this->selectedThemeId);
        $defaultOrder = $theme
            ? array_flip(SectionRegistry::defaultsFor($theme->slug, self::PAGE))
            : [];

        $row = TenantPageSection::query()->firstOrCreate(
            [
                'theme_id' => $this->selectedThemeId,
                'page' => self::PAGE,
                'section_key' => $sectionKey,
            ],
            [
                'sort_order' => $defaultOrder[$sectionKey] ?? 0,
                'is_visible' => true,
            ]
        );

        $row->update(['is_visible' => !$row->is_visible]);

        $this->toast('Section visibility updated.');
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.store.page-builder-page';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Page Builder',
            'badge' => 'Storefront',
            'description' => 'Reorder and show/hide sections on your storefront Home page.',
        ];
    }

    protected function pageData(): array
    {
        $themes = app(TenantPanelRepository::class)->themes();

        $sections = [];
        $selectedTheme = $themes->firstWhere('id', $this->selectedThemeId);

        if ($selectedTheme) {
            $labels = SectionRegistry::labelsFor($selectedTheme->slug, self::PAGE);
            $defaultOrder = array_keys($labels);

            $rows = TenantPageSection::query()
                ->where('theme_id', $selectedTheme->id)
                ->where('page', self::PAGE)
                ->get()
                ->keyBy('section_key');

            $hasCustomOrder = $rows->isNotEmpty();

            $orderedKeys = $hasCustomOrder
                ? $rows->sortBy('sort_order')->pluck('section_key')->all()
                : $defaultOrder;

            // Include any registry keys not yet represented by a row (e.g. a
            // theme update added a new section) at the end, in registry order.
            foreach ($defaultOrder as $key) {
                if (!in_array($key, $orderedKeys, true)) {
                    $orderedKeys[] = $key;
                }
            }

            foreach ($orderedKeys as $key) {
                if (!array_key_exists($key, $labels)) {
                    continue;
                }

                $row = $rows->get($key);

                $sections[] = [
                    'section_key' => $key,
                    'label' => $labels[$key],
                    'is_visible' => $row ? (bool) $row->is_visible : true,
                ];
            }
        }

        return array_merge(parent::pageData(), [
            'themes' => $themes,
            'sections' => $sections,
        ]);
    }
}
