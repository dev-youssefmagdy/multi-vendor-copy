<?php

namespace App\Support;

/**
 * Canonical set of theme color CSS variable keys (without the leading `--`).
 * Every theme's colorDefaults() must define a value for each of these keys.
 */
class ThemeColorKeys
{
    public const PRIMARY = 'color-primary';
    public const SECONDARY = 'color-secondary';
    public const BACKGROUND = 'color-background';
    public const TEXT = 'color-text';
    public const ACCENT = 'color-accent';
    public const HEADER_BG = 'color-header-bg';
    public const FOOTER_BG = 'color-footer-bg';

    /** @return string[] */
    public static function all(): array
    {
        return [
            self::PRIMARY,
            self::SECONDARY,
            self::BACKGROUND,
            self::TEXT,
            self::ACCENT,
            self::HEADER_BG,
            self::FOOTER_BG,
        ];
    }

    public static function label(string $key): string
    {
        return str($key)->after('color-')->headline()->toString();
    }
}
