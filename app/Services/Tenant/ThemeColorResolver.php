<?php

namespace App\Services\Tenant;

use App\Contracts\TemplateStrategy;
use App\Models\Tenant\Theme;
use App\Models\Tenant\TenantThemeColor;
use App\Models\ThemeColorDefault;
use App\Support\ThemeColorKeys;

/**
 * Resolves the final set of CSS color variables for a theme, layering:
 *   1. The theme strategy's hardcoded defaults (base fallback)
 *   2. Admin-set global defaults for that theme slug (theme_color_defaults, central DB)
 *   3. The tenant's own overrides (tenant_theme_colors, tenant DB)
 */
class ThemeColorResolver
{
    public function __construct(private readonly TemplateRegistryService $registry)
    {
    }

    /**
     * @return array<string, string> variable key => hex value
     */
    public function resolve(Theme $theme): array
    {
        $strategy = $this->registry->resolve((string) $theme->slug);

        $values = $strategy->colorDefaults();
        $values = array_merge($values, $this->globalDefaults($strategy));
        $values = array_merge($values, $this->tenantOverrides($theme));

        return $values;
    }

    /**
     * Build an inline `--color-x: #hex;` string suitable for a style="" attribute.
     */
    public function resolveInlineStyle(Theme $theme): string
    {
        $pairs = [];
        foreach ($this->resolve($theme) as $key => $value) {
            $pairs[] = "--{$key}: {$value}";
        }

        return implode('; ', $pairs);
    }

    /** @return array<string, string> */
    private function globalDefaults(TemplateStrategy $strategy): array
    {
        return tenancy()->central(fn() => ThemeColorDefault::query()
            ->where('theme_slug', $strategy->slug())
            ->whereIn('variable_key', ThemeColorKeys::all())
            ->pluck('value', 'variable_key')
            ->all());
    }

    /** @return array<string, string> */
    private function tenantOverrides(Theme $theme): array
    {
        return TenantThemeColor::query()
            ->where('theme_id', $theme->id)
            ->whereIn('variable_key', ThemeColorKeys::all())
            ->pluck('value', 'variable_key')
            ->all();
    }
}
