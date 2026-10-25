<?php

namespace App\Services\Tenant;

use App\Contracts\TemplateStrategy;
use App\Models\Country;
use App\Models\HomeVariant;
use App\Models\Tenant\Theme;
use App\Models\Tenant\TenantHomeVariant;
use App\Services\Preview\PreviewOverrides;
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
    /** Request attribute holding the per-request resolveFor() memo. */
    public const MEMO_ATTRIBUTE = '_home_variant_resolver_memo';

    /**
     * Memoized per request (keyed by tenant, theme, country and preview key):
     * the layout composer and HomePage both resolve the same variant on every
     * page load. The memo lives on the request's attributes, so it never
     * outlives the request (queue workers / Octane get a fresh one).
     *
     * @return HomeVariant|null
     */
    public function resolveFor(Theme $theme, ?Country $country): ?HomeVariant
    {
        $previewKey = PreviewOverrides::active() ? PreviewOverrides::homepageVariantKey() : null;
        $memoKey = implode('|', [
            (string) (tenant('id') ?? 'central'),
            (string) $theme->id,
            (string) $theme->slug,
            (string) ($country?->id ?? ''),
            (string) ($previewKey ?? ''),
        ]);

        $attributes = request()->attributes;
        $memo = $attributes->get(self::MEMO_ATTRIBUTE, []);

        if (!array_key_exists($memoKey, $memo)) {
            $memo[$memoKey] = $this->resolveUncached($theme, $country);
            $attributes->set(self::MEMO_ATTRIBUTE, $memo);
        }

        return $memo[$memoKey];
    }

    /**
     * HomeVariant uses the central connection (CentralConnection trait), so
     * no tenancy()->central() round-trip is needed for its queries.
     */
    private function resolveUncached(Theme $theme, ?Country $country): ?HomeVariant
    {
        if (PreviewOverrides::active() && PreviewOverrides::homepageVariantKey()) {
            $forced = HomeVariant::query()
                ->forTheme($theme->slug)
                ->where('key', PreviewOverrides::homepageVariantKey())
                ->first();

            if ($forced) {
                return $forced;
            }
        }

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
            $variant = HomeVariant::query()->find($row->home_variant_id);
            if ($variant) {
                return $variant;
            }
        }

        return HomeVariant::query()
            ->forTheme($theme->slug)
            ->where('is_default', true)
            ->first();
    }

    /**
     * @param string[]|null $fallback Tenant's Page Builder order for the resolved variant,
     *                                or null if the tenant hasn't customized it.
     * @return string[] section keys in order
     */
    public function sectionsFor(?HomeVariant $variant, string $themeSlug, ?array $fallback): array
    {
        if (PreviewOverrides::active() && PreviewOverrides::sections() !== null) {
            return PreviewOverrides::sections();
        }

        // The tenant's own per-variant Page Builder order wins over the variant's
        // catalog default, since Page Builder exists to let tenants override it.
        if ($fallback !== null) {
            return $fallback;
        }

        if ($variant && $variant->sections) {
            return $variant->sections;
        }

        return SectionRegistry::defaultsFor($themeSlug, 'home');
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
