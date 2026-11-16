<?php

declare(strict_types=1);

namespace App\Support\Tenant;

use App\Models\Country;
use App\Models\Tenant\ThemeCountry;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\CountryDetectorService;
use App\Support\CountryScope;

/**
 * Vendor dashboard "Live Store Preview" + "Edit your storefront" country selector.
 *
 * One option per country the store sells in (plus "All countries" = the store-wide
 * defaults). Each option carries:
 *   - preview_url: the storefront rendered as a visitor from that country
 *     (?preview_country=XX, request-only, see CountryDetectorService);
 *   - links: the editor shortcuts pointed at what that country actually sees: the theme
 *     and home edition live there (country override or store-wide choice), its banners.
 *
 * The live theme / edition are resolved by StorefrontRepository::forCountry(), i.e. the
 * exact rules the storefront uses, so the shortcuts can never drift from the storefront.
 */
final class StorefrontCountryShortcuts
{
    public const ALL = 'all';

    /**
     * @return list<array{code: string, id: int|null, iso2: string|null, name: string, emoji: string|null,
     *     preview_url: string, links: array{template: string, colors: string, banners: string, homepage: string, logo: string}}>
     */
    public static function options(): array
    {
        $storefrontUrl = url('/');
        $repository = app(StorefrontRepository::class);

        $options = [[
            'code' => self::ALL,
            'id' => null,
            'iso2' => null,
            'name' => __('All countries'),
            'emoji' => null,
            'preview_url' => $storefrontUrl,
            'links' => self::defaultLinks(),
        ]];

        foreach (CountryScope::tenantCountries() as $country) {
            $iso2 = strtoupper((string) $country->iso2);
            if (strlen($iso2) !== 2) {
                continue;
            }

            $options[] = [
                'code' => strtolower($iso2),
                'id' => (int) $country->id,
                'iso2' => $iso2,
                'name' => (string) $country->name,
                'emoji' => $country->flag_emoji ?: null,
                'preview_url' => $storefrontUrl.'?'.http_build_query([CountryDetectorService::PREVIEW_PARAM => $iso2]),
                'links' => self::countryLinks($repository->forCountry($country), $country),
            ];
        }

        return $options;
    }

    /** Store-wide shortcuts (no country chosen / "All countries"). */
    public static function defaultLinks(): array
    {
        return [
            'template' => route('tenant.store.themes'),
            'colors' => route('tenant.store.appearance', ['tab' => 'colors']),
            'banners' => route('tenant.store.banners.index'),
            'homepage' => route('tenant.store.page-builder'),
            'logo' => route('tenant.store.appearance', ['tab' => 'general']),
        ];
    }

    private static function countryLinks(StorefrontRepository $repository, Country $country): array
    {
        $links = self::defaultLinks();

        // Banners can target countries: open the list filtered to this one.
        $links['banners'] = route('tenant.store.banners.index', ['country' => $country->id]);

        $theme = $repository->currentTheme();
        $variant = $theme ? $repository->currentHomeVariant() : null;

        if (!$theme) {
            return $links;
        }

        // Template: the live theme's per-country edition picker, filtered to this country.
        // A country not enabled for that theme yet opens the theme's Countries list instead.
        $themeHasCountries = $theme->relationLoaded('countries')
            ? $theme->countries->isNotEmpty()
            : ThemeCountry::query()->where('theme_id', $theme->id)->exists();

        if ($themeHasCountries) {
            $enabled = ThemeCountry::query()
                ->where('theme_id', $theme->id)
                ->where('country_id', $country->id)
                ->where('is_enabled', true)
                ->exists();

            $links['template'] = route('tenant.store.themes', [
                'open' => $enabled ? 'variants' : 'countries',
                'theme' => $theme->id,
                'country' => strtoupper((string) $country->iso2),
            ]);
        }

        if ($variant) {
            // Colors and the home page layout are per theme edition: open the one live here.
            $links['colors'] = route('tenant.store.appearance', ['tab' => 'colors', 'theme' => $theme->id, 'variant' => $variant->id]);
            $links['homepage'] = route('tenant.store.page-builder', ['theme' => $theme->id, 'variant' => $variant->id]);
        }

        return $links;
    }
}
