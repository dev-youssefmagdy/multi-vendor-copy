<?php

namespace App\Services\Tenant;

use App\Contracts\TemplateStrategy;
use App\Models\Country;
use App\Models\HomeVariant;
use App\Models\Tenant\Theme;
use App\Models\Tenant\TenantHomeVariant;
use App\Services\Tenant\PageBuilder\SectionRegistry;

/**
 * Resolves which HomeVariant (if any) applies to the current tenant/theme,
 * optionally scoped by country, and derives the section order, color
 * overrides, and view to use from it.
 *
 * Selection mirrors the country-specificity pattern used for themes
 * (StorefrontRepository::currentTheme): a country-specific tenant choice
 * wins over the tenant-wide default, which wins over the theme's seeded
 * "is_default" catalog variant.
 */
class HomeVariantResolver
{
    /**
     * @return HomeVariant|null
     */
    public function resolveFor(Theme $theme, ?Country $country): ?HomeVariant
    {
        $row = null;

        if ($country) {
            $row = TenantHomeVariant::query()
                ->where('theme_id', $theme->id)
                ->where('country_id', $country->id)
                ->first();
        }

        $row ??= TenantHomeVariant::query()
            ->where('theme_id', $theme->id)
            ->whereNull('country_id')
            ->first();

        if ($row) {
            $variant = tenancy()->central(fn() => HomeVariant::query()->find($row->home_variant_id));
            if ($variant) {
                return $variant;
            }
        }

        return tenancy()->central(fn() => HomeVariant::query()
            ->forTheme($theme->slug)
            ->where('is_default', true)
            ->first());
    }

    /**
     * @param string[]|null $fallback Tenant's Page Builder order, or null if the tenant
     *                                hasn't customized this theme's sections.
     * @return string[] section keys in order
     */
    public function sectionsFor(?HomeVariant $variant, string $themeSlug, ?array $fallback): array
    {
        if ($variant && $variant->sections) {
            return $variant->sections;
        }

        return $fallback ?? SectionRegistry::defaultsFor($themeSlug, 'home');
    }

    /** @return array<string, string> variable key => hex value */
    public function colorOverridesFor(?HomeVariant $variant): array
    {
        return $variant?->colors ?? [];
    }

    public function viewFor(?HomeVariant $variant, TemplateStrategy $strategy): string
    {
        return $variant?->view ?: $strategy->pageView('home');
    }
}
