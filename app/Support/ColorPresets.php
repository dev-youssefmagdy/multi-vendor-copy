<?php

namespace App\Support;

/**
 * Curated palettes offered when creating a home page variant, keyed by the
 * same variable keys as ThemeColorKeys. Presets only prefill the color form —
 * the variant always stores concrete hex values, never a preset reference.
 */
class ColorPresets
{
    private const PRESETS = [
        'Ocean Blue' => [
            ThemeColorKeys::PRIMARY => '#0ea5e9',
            ThemeColorKeys::SECONDARY => '#0369a1',
            ThemeColorKeys::BACKGROUND => '#f0f9ff',
            ThemeColorKeys::TEXT => '#0c1a24',
            ThemeColorKeys::ACCENT => '#22d3ee',
            ThemeColorKeys::HEADER_BG => '#0c4a6e',
            ThemeColorKeys::FOOTER_BG => '#082f49',
        ],
        'Sunset' => [
            ThemeColorKeys::PRIMARY => '#f97316',
            ThemeColorKeys::SECONDARY => '#ea580c',
            ThemeColorKeys::BACKGROUND => '#fff7ed',
            ThemeColorKeys::TEXT => '#1c1207',
            ThemeColorKeys::ACCENT => '#f43f5e',
            ThemeColorKeys::HEADER_BG => '#9a3412',
            ThemeColorKeys::FOOTER_BG => '#7c2d12',
        ],
        'Forest' => [
            ThemeColorKeys::PRIMARY => '#16a34a',
            ThemeColorKeys::SECONDARY => '#15803d',
            ThemeColorKeys::BACKGROUND => '#f0fdf4',
            ThemeColorKeys::TEXT => '#0d1f13',
            ThemeColorKeys::ACCENT => '#84cc16',
            ThemeColorKeys::HEADER_BG => '#14532d',
            ThemeColorKeys::FOOTER_BG => '#052e16',
        ],
        'Royal Purple' => [
            ThemeColorKeys::PRIMARY => '#7c3aed',
            ThemeColorKeys::SECONDARY => '#6d28d9',
            ThemeColorKeys::BACKGROUND => '#faf5ff',
            ThemeColorKeys::TEXT => '#1a1225',
            ThemeColorKeys::ACCENT => '#c026d3',
            ThemeColorKeys::HEADER_BG => '#4c1d95',
            ThemeColorKeys::FOOTER_BG => '#2e1065',
        ],
        'Monochrome' => [
            ThemeColorKeys::PRIMARY => '#111827',
            ThemeColorKeys::SECONDARY => '#374151',
            ThemeColorKeys::BACKGROUND => '#ffffff',
            ThemeColorKeys::TEXT => '#111827',
            ThemeColorKeys::ACCENT => '#6b7280',
            ThemeColorKeys::HEADER_BG => '#000000',
            ThemeColorKeys::FOOTER_BG => '#111827',
        ],
    ];

    /** @return array<string, array<string, string>> preset name => [variable key => hex] */
    public static function all(): array
    {
        return self::PRESETS;
    }

    /** @return array<string, string>|null */
    public static function get(string $name): ?array
    {
        return self::PRESETS[$name] ?? null;
    }
}
