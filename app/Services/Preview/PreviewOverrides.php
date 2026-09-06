<?php

namespace App\Services\Preview;

/**
 * Request-scoped overrides applied while rendering the dedicated "preview"
 * tenant's storefront (see ForcePreviewTemplate). Never touches real tenant
 * data — values live only as static state for the lifetime of the request.
 */
class PreviewOverrides
{
    private static bool $active = false;

    /** @var array<string, string> variable key => hex value */
    private static array $colors = [];

    private static ?string $homepageVariantKey = null;

    private static ?array $sections = null;

    public static function activate(): void
    {
        static::$active = true;
    }

    public static function active(): bool
    {
        return static::$active;
    }

    /** @param array<string, string> $colors */
    public static function setColors(array $colors): void
    {
        static::$colors = $colors;
    }

    /** @return array<string, string> */
    public static function colors(): array
    {
        return static::$colors;
    }

    public static function setHomepageVariantKey(?string $key): void
    {
        static::$homepageVariantKey = $key;
    }

    public static function homepageVariantKey(): ?string
    {
        return static::$homepageVariantKey;
    }

    public static function setSections(?array $sections): void
    {
        static::$sections = $sections;
    }

    public static function sections(): ?array
    {
        return static::$sections;
    }

    /** Clear all overrides (useful in tests). */
    public static function clear(): void
    {
        static::$active = false;
        static::$colors = [];
        static::$homepageVariantKey = null;
        static::$sections = null;
    }
}
