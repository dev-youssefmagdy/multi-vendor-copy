<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Store;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Store\UpdateThemeCountriesRequest;
use App\Models\HomeVariant;
use App\Models\Tenant\TenantHomeVariant;
use App\Models\Tenant\TenantPageSection;
use App\Models\Tenant\Theme;
use App\Models\Tenant\ThemeCountry;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use App\Support\Tenant\Metric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class ThemesController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {}

    public function index(Request $request): View
    {
        $themes = $this->repo->themes();

        $slugs = $themes->pluck('slug')->map(fn ($s) => strtolower($s))->all();
        $allVariants = tenancy()->central(fn () => HomeVariant::query()
            ->whereIn('theme_slug', $slugs)
            ->where('is_active', true)
            ->orderBy('theme_slug')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
        );

        $activeUniversalTheme = $themes->first(fn (Theme $t) => $t->is_universal && $t->is_active);
        $activeVariantId = null;
        if ($activeUniversalTheme) {
            $activeVariantId = TenantHomeVariant::query()
                ->where('theme_id', $activeUniversalTheme->id)
                ->whereNull('country_id')
                ->value('home_variant_id');
        }

        $variantCards = $allVariants->map(function (HomeVariant $variant) use ($themes, $activeVariantId) {
            $theme = $themes->first(fn (Theme $t) => strtolower($t->slug) === strtolower($variant->theme_slug));
            if (! $theme) {
                return null;
            }

            $isUniversal = (bool) $theme->is_universal;
            $enabledCount = $theme->countries->where('is_enabled', true)->count();
            $totalCount = $theme->countries->count();

            $isActive = $isUniversal
                ? ($activeVariantId !== null && (int) $activeVariantId === (int) $variant->id)
                : (bool) $theme->is_active;

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

            $variantKey = tenancy()->central(fn () => $variant->key ?? null);
            $previewUrl = $this->buildPreviewUrl(strtolower($theme->slug), $theme->id, $variantKey ?: null, $variant->id);

            $domain = tenant()?->domains()->first()?->domain;
            $storefrontUrl = $isActive && $domain
                ? ((str_starts_with($domain, 'http') ? '' : 'https://').$domain)
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

        return view('tenant.pages.store.themes.index', [
            'activeThemeName' => $activeVariantName,
            'variantCards' => $variantCards,
            'stats' => Metric::cards([
                ['label' => 'Variants', 'value' => count($variantCards), 'format' => 'number', 'caption' => 'Available home page layouts', 'dot' => 'dot-cyan'],
                ['label' => 'Active', 'value' => collect($variantCards)->where('is_active', true)->count(), 'format' => 'number', 'caption' => 'Currently live variant', 'dot' => 'dot-green'],
                ['label' => 'Themes', 'value' => $themes->count(), 'format' => 'number', 'caption' => 'Base themes available', 'dot' => 'dot-amber'],
            ]),
        ]);
    }

    private function buildPreviewUrl(string $themeSlug, int $themeId, ?string $variantKey, ?int $variantId = null): string
    {
        $query = ['theme' => $themeSlug];

        if ($variantKey !== null) {
            $query['homepage_variant'] = $variantKey;
        }

        $sectionKeys = TenantPageSection::query()
            ->where('theme_id', $themeId)
            ->when(
                $variantId,
                fn ($q) => $q->where('home_variant_id', $variantId),
                fn ($q) => $q->whereNull('home_variant_id')
            )
            ->where('page', 'home')
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->pluck('section_key')
            ->all();

        if (! empty($sectionKeys)) {
            $query['sections'] = implode(',', $sectionKeys);
        }

        $centralDomain = config('tenancy.central_domains.0')
            ?: (parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost');
        $scheme = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_SCHEME) ?: 'http';

        return $scheme.'://'.$centralDomain.'/preview?'.http_build_query($query);
    }

    public function activate(Theme $theme): JsonResponse
    {
        $this->service->activateTheme($theme);

        return $this->success('Theme activated successfully.');
    }

    public function activateVariant(Theme $theme, int $variant): JsonResponse
    {
        $this->service->activateTheme($theme);

        TenantHomeVariant::query()->updateOrCreate(
            ['theme_id' => $theme->id, 'country_id' => null],
            ['home_variant_id' => $variant]
        );

        return $this->success('Variant activated successfully.');
    }

    public function deactivate(Theme $theme): JsonResponse
    {
        try {
            $this->service->deactivateTheme($theme);
        } catch (\DomainException $e) {
            return $this->failure($e->getMessage(), 422);
        }

        return $this->success('Theme deactivated.');
    }

    public function countries(Theme $theme): JsonResponse
    {
        $theme->load('countries');

        return response()->json([
            'data' => [
                'theme_id' => $theme->id,
                'theme_name' => $theme->name,
                'countries' => $theme->countries->map(fn (ThemeCountry $country) => [
                    'country_id' => (int) $country->country_id,
                    'name' => $country->name,
                    'iso2' => $country->iso2,
                    'flag_emoji' => $country->flag_emoji,
                    'enabled' => (bool) $country->is_enabled,
                ])->values(),
            ],
        ]);
    }

    public function updateCountries(UpdateThemeCountriesRequest $request, Theme $theme): JsonResponse
    {
        $theme->load('countries');
        $allowedIds = $theme->countries->pluck('country_id')->map(fn ($v) => (int) $v)->all();

        $enabled = array_values(array_intersect(
            array_map('intval', $request->validated('country_ids') ?? []),
            $allowedIds
        ));

        ThemeCountry::query()->where('theme_id', $theme->id)->update(['is_enabled' => false]);

        if (! empty($enabled)) {
            ThemeCountry::query()
                ->where('theme_id', $theme->id)
                ->whereIn('country_id', $enabled)
                ->update(['is_enabled' => true]);
        }

        return $this->success('Theme countries updated successfully.');
    }
}
