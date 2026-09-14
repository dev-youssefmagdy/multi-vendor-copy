<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Store;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Country;
use App\Models\HomeVariant;
use App\Models\Tenant\TenantHomeVariant;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class HomeVariantsController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
    ) {
    }

    public function index(Request $request): View
    {
        $themes = $this->repo->themes();
        $selectedThemeId = $this->resolveThemeId($request, $themes);
        $selectedTheme = $themes->firstWhere('id', $selectedThemeId);

        $rows = [];
        $availableVariants = collect();

        if ($selectedTheme) {
            $slug = strtolower($selectedTheme->slug);

            $availableVariants = tenancy()->central(fn () => HomeVariant::query()
                ->where('theme_slug', $slug)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get()
            );

            $selections = TenantHomeVariant::query()
                ->where('theme_id', $selectedTheme->id)
                ->get()
                ->keyBy(fn ($row) => $row->country_id ?? 0);

            $countries = Country::query()
                ->where('is_active_for_tenants', true)
                ->orderBy('name')
                ->get();

            $rowDefs = $countries->map(fn (Country $country) => [
                'country_id' => $country->id,
                'label' => trim(($country->flag_emoji ?? '').' '.$country->name),
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

        $dtRows = collect($rows)->map(fn (array $row) => [
            e($row['label']),
            view('tenant.pages.store.home-variants._cols.variant-select', ['row' => $row, 'availableVariants' => $availableVariants])->render(),
            view('tenant.pages.store.home-variants._cols.colors', ['row' => $row, 'availableVariants' => $availableVariants])->render(),
        ])->all();

        return view('tenant.pages.store.home-variants.index', [
            'themes' => $themes,
            'selectedThemeId' => $selectedThemeId,
            'selectedTheme' => $selectedTheme,
            'availableVariants' => $availableVariants,
            'rows' => $dtRows,
            'columns' => [
                TableColumn::make('scope', 'Scope')->orderable(false),
                TableColumn::make('variant', 'Variant')->orderable(false),
                TableColumn::make('colors', 'Colors')->orderable(false),
            ],
        ]);
    }

    public function selectVariant(Request $request): JsonResponse
    {
        $themeId = (int) $request->input('theme_id');
        $countryId = $request->input('country_id') !== null && $request->input('country_id') !== ''
            ? (int) $request->input('country_id')
            : null;
        $variantId = $request->input('variant_id') !== null && $request->input('variant_id') !== ''
            ? (int) $request->input('variant_id')
            : null;

        if (!$themeId) {
            return $this->failure('A theme must be selected.', 422);
        }

        if (!$variantId) {
            TenantHomeVariant::query()
                ->where('theme_id', $themeId)
                ->where('country_id', $countryId)
                ->delete();

            return $this->success('Reverted to the theme\'s default variant.');
        }

        TenantHomeVariant::query()->updateOrCreate(
            ['theme_id' => $themeId, 'country_id' => $countryId],
            ['home_variant_id' => $variantId]
        );

        return $this->success('Home page variant saved.');
    }

    private function resolveThemeId(Request $request, Collection $themes): ?int
    {
        $themeId = $request->query('theme');

        if ($themeId && $themes->firstWhere('id', (int) $themeId)) {
            return (int) $themeId;
        }

        return $themes->firstWhere('is_active', true)?->id ?? $themes->first()?->id;
    }
}
