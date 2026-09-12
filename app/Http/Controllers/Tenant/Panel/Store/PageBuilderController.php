<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Store;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\HomeVariant;
use App\Models\Tenant\TenantPageSection;
use App\Models\Tenant\Theme;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\PageBuilder\SectionRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class PageBuilderController extends PanelController
{
    private const PAGE = 'home';

    public function __construct(
        private readonly TenantPanelRepository $repo,
    ) {
    }

    public function index(Request $request): View
    {
        $themes = $this->repo->themes();

        $selectedThemeId = $this->resolveThemeId($request, $themes);
        $availableVariants = $this->variantsForTheme($themes->firstWhere('id', $selectedThemeId));
        $selectedHomeVariantId = $this->resolveVariantId($request, $selectedThemeId, $availableVariants);

        $selectedTheme = $themes->firstWhere('id', $selectedThemeId);
        $sections = [];

        if ($selectedTheme) {
            $labels = SectionRegistry::labelsFor($selectedTheme->slug, self::PAGE);
            $defaultOrder = array_keys($labels);

            $rows = TenantPageSection::query()
                ->where('theme_id', $selectedTheme->id)
                ->when(
                    $selectedHomeVariantId,
                    fn ($q) => $q->where('home_variant_id', $selectedHomeVariantId),
                    fn ($q) => $q->whereNull('home_variant_id')
                )
                ->where('page', self::PAGE)
                ->get()
                ->keyBy('section_key');

            $hasCustomOrder = $rows->isNotEmpty();

            $orderedKeys = $hasCustomOrder
                ? $rows->sortBy('sort_order')->pluck('section_key')->all()
                : $defaultOrder;

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

        return view('tenant.pages.store.page-builder.index', [
            'themes' => $themes,
            'availableVariants' => $availableVariants,
            'selectedThemeId' => $selectedThemeId,
            'selectedHomeVariantId' => $selectedHomeVariantId,
            'sections' => $sections,
        ]);
    }

    public function updateOrder(Request $request): JsonResponse
    {
        $themeId = (int) $request->input('theme_id');
        $homeVariantId = $request->input('home_variant_id') !== null && $request->input('home_variant_id') !== ''
            ? (int) $request->input('home_variant_id')
            : null;
        $orderedKeys = (array) $request->input('ids', []);

        if (!$themeId) {
            return $this->failure('A theme must be selected.', 422);
        }

        foreach ($orderedKeys as $index => $key) {
            TenantPageSection::query()->updateOrCreate(
                [
                    'theme_id' => $themeId,
                    'home_variant_id' => $homeVariantId,
                    'page' => self::PAGE,
                    'section_key' => $key,
                ],
                [
                    'sort_order' => $index,
                ]
            );
        }

        return $this->success('Section order saved.');
    }

    public function toggleVisibility(Request $request, string $section): JsonResponse
    {
        $themeId = (int) $request->input('theme_id');
        $homeVariantId = $request->input('home_variant_id') !== null && $request->input('home_variant_id') !== ''
            ? (int) $request->input('home_variant_id')
            : null;

        if (!$themeId) {
            return $this->failure('A theme must be selected.', 422);
        }

        $theme = Theme::query()->find($themeId);
        $defaultOrder = $theme
            ? array_flip(SectionRegistry::defaultsFor($theme->slug, self::PAGE))
            : [];

        $row = TenantPageSection::query()->firstOrCreate(
            [
                'theme_id' => $themeId,
                'home_variant_id' => $homeVariantId,
                'page' => self::PAGE,
                'section_key' => $section,
            ],
            [
                'sort_order' => $defaultOrder[$section] ?? 0,
                'is_visible' => true,
            ]
        );

        $row->update(['is_visible' => !$row->is_visible]);

        return $this->success('Section visibility updated.');
    }

    private function resolveThemeId(Request $request, Collection $themes): ?int
    {
        $themeId = $request->query('theme');

        if ($themeId && $themes->firstWhere('id', (int) $themeId)) {
            return (int) $themeId;
        }

        return $themes->firstWhere('is_active', true)?->id ?? $themes->first()?->id;
    }

    private function resolveVariantId(Request $request, ?int $themeId, Collection $availableVariants): ?int
    {
        if ($request->has('variant')) {
            $variantId = $request->query('variant');

            return $variantId !== null && $variantId !== '' ? (int) $variantId : null;
        }

        $theme = $themeId ? Theme::query()->find($themeId) : null;

        if (!$theme) {
            return null;
        }

        return tenancy()->central(fn () => HomeVariant::query()
            ->forTheme($theme->slug)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->value('id')
        );
    }

    private function variantsForTheme(?Theme $theme): Collection
    {
        if (!$theme) {
            return collect();
        }

        return tenancy()->central(fn () => HomeVariant::query()
            ->forTheme($theme->slug)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
        );
    }
}
