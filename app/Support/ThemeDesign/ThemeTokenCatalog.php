<?php

namespace App\Support\ThemeDesign;

/**
 * Every storefront design token (CSS custom property) a tenant may change, per theme + home variant.
 * Data lives in resources/theme-tokens/catalog.json: for each variant a human label, group, a description of
 * what it affects, and the default value taken from what the storefront renders today (so listing a token
 * never changes a design until the tenant edits it).
 *
 * Regenerate with the script described in resources/theme-tokens/README.md when a theme gains/loses a token.
 */
final class ThemeTokenCatalog
{
    /** @var array<string, mixed>|null */
    private static ?array $data = null;

    /** @return array<string, mixed> */
    private static function data(): array
    {
        return self::$data ??= (array) json_decode((string) file_get_contents(resource_path('theme-tokens/catalog.json')), true);
    }

    /** @return list<string> color groups in display order */
    public static function groups(): array
    {
        return (array) (self::data()['groups'] ?? []);
    }

    /**
     * @return array{theme: string, variant: string, name: string, description: string, fonts: array{ltr: string, rtl: string}, tokens: array<string, array{label: string, group: string, description: string, default: string, uses: int, areas: list<string>}>}|null
     */
    public static function forVariant(string $themeSlug, string $variantKey): ?array
    {
        return self::data()['variants'][strtolower($themeSlug) . '-' . strtolower($variantKey)] ?? null;
    }

    /** @return array<string, string> token name => default value */
    public static function defaults(string $themeSlug, string $variantKey): array
    {
        return array_map(fn (array $token) => $token['default'], self::forVariant($themeSlug, $variantKey)['tokens'] ?? []);
    }

    /** @return array{ltr: string, rtl: string} */
    public static function defaultFonts(string $themeSlug, string $variantKey): array
    {
        return self::forVariant($themeSlug, $variantKey)['fonts'] ?? ['ltr' => ThemeFonts::DEFAULT_LTR, 'rtl' => ThemeFonts::DEFAULT_RTL];
    }
}
