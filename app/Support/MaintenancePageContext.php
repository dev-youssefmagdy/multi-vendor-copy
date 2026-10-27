<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant\Theme;
use Illuminate\Http\Request;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * Decides which branding the `errors/503` (artisan down) page uses.
 *
 * Runs while the app is in maintenance mode, i.e. before tenancy is
 * initialised and possibly with a degraded DB, so every lookup is best-effort
 * and falls back to a neutral palette instead of throwing.
 */
class MaintenancePageContext
{
    public const PALETTES = [
        'website' => ['accent' => '#f97316', 'soft' => '#fff7ed', 'dark' => '#1f2937', 'font' => "'Inter', system-ui, sans-serif"],
        'admin'   => ['accent' => '#4f46e5', 'soft' => '#eef2ff', 'dark' => '#0f172a', 'font' => "'Inter', system-ui, sans-serif"],
        'vendor'  => ['accent' => '#0d9488', 'soft' => '#f0fdfa', 'dark' => '#0f172a', 'font' => "'Inter', system-ui, sans-serif"],
        'owner'   => ['accent' => '#7c3aed', 'soft' => '#f5f3ff', 'dark' => '#1e1b4b', 'font' => "'Inter', system-ui, sans-serif"],
        'elora'   => ['accent' => '#e11d48', 'soft' => '#fff1f2', 'dark' => '#1c1917', 'font' => "'Inter', system-ui, sans-serif"],
        'ecommet' => ['accent' => '#16a34a', 'soft' => '#f0fdf4', 'dark' => '#14211a', 'font' => "'Inter', system-ui, sans-serif"],
        'souqify' => ['accent' => '#1d4ed8', 'soft' => '#eff6ff', 'dark' => '#0f172a', 'font' => "'Outfit', system-ui, sans-serif"],
        'custom'  => ['accent' => '#475569', 'soft' => '#f8fafc', 'dark' => '#0f172a', 'font' => "'Inter', system-ui, sans-serif"],
    ];

    /** @return array{key: string, accent: string, soft: string, dark: string, font: string, is_panel: bool} */
    public static function resolve(Request $request): array
    {
        $isCentral = in_array($request->getHost(), (array) config('tenancy.central_domains', []), true);

        if ($request->is('owner', 'owner/*')) {
            $key = 'owner';
        } elseif ($request->is('admin', 'admin/*')) {
            $key = $isCentral ? 'admin' : 'vendor';
        } elseif ($isCentral) {
            $key = 'website';
        } else {
            $key = self::tenantThemeKey($request->getHost());
        }

        return ['key' => $key, 'is_panel' => in_array($key, ['admin', 'vendor', 'owner'], true)]
            + self::PALETTES[$key];
    }

    private static function tenantThemeKey(string $host): string
    {
        try {
            $tenant = Domain::query()->where('domain', $host)->first()?->tenant;
            if (!$tenant) {
                return 'website';
            }

            $slug = (string) $tenant->run(fn () => Theme::query()->where('is_active', true)->value('slug'));
            $slug = preg_replace('/-\d+$/', '', $slug) ?? '';

            return array_key_exists($slug, self::PALETTES) ? $slug : 'custom';
        } catch (\Throwable) {
            return 'website';
        }
    }
}
