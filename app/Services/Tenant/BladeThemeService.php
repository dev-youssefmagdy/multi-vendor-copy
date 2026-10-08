<?php

namespace App\Services\Tenant;

use App\Models\BladeTheme;
use App\Models\HomeVariant;
use App\Models\Tenant as TenantModel;
use App\Models\Tenant\TenantPageSection;
use App\Models\Tenant\Theme;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Handles secure upload, admin review, and activation of vendor-authored
 * Blade storefront themes.
 *
 * SECURITY NOTE: Blade themes ARE compiled and executed by Laravel's view engine.
 * @php blocks are permitted (they're needed for trivial local variable
 * assignment in section templates) — the denylist instead targets the
 * actual dangerous primitives: shell execution, eval, and dynamic function
 * calls. This is still a first-pass filter only, not a substitute for
 * admin review — Blade's `{{ }}` compiles to a full PHP echo of any
 * expression, which a text denylist cannot fully close off. A theme is
 * never live until an admin explicitly approves it, and the vendor then
 * activates it from Store → Themes — uploads always land as `pending` and
 * are never auto-approved or auto-activated.
 */
class BladeThemeService
{
    /** Required files — every vendor theme must include these. */
    private const REQUIRED_FILES = [
        'layout/app.blade.php',
        'pages/home/index.blade.php',
    ];

    /** Denylist scan — blocks the literal patterns; NOT a substitute for admin review. */
    private const BLOCKED_PATTERNS = [
        '<?php', '<?=', '@php', 'system(', 'exec(', 'shell_exec(', 'passthru(', 'eval(',
        'proc_open(', 'popen(', 'assert(', 'call_user_func(', 'call_user_func_array(',
        '`', // backtick shell execution operator
    ];

    private const ALLOWED_EXTENSIONS = [
        'blade.php', 'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'woff', 'woff2',
        'md', 'txt', 'json', 'ico', 'webmanifest',
    ];

    private const BASE_DIR = 'tenant-blade-themes';

    public function baseStoragePath(string $tenantId): string
    {
        return self::BASE_DIR . '/' . $tenantId;
    }

    public function versionStoragePath(string $tenantId, string $version): string
    {
        return $this->baseStoragePath($tenantId) . '/' . $version;
    }

    public function liveViewsPath(string $tenantId): string
    {

        return storage_path("app/tenants/{$tenantId}/theme/views");
    }

    public function upload(TenantModel|string $tenant, UploadedFile $file): BladeTheme
    {
        $tenantId = (string) (is_string($tenant) ? $tenant : $tenant->getTenantKey());

        $absolutePath = $file->getRealPath();
        if (!$absolutePath) {
            throw new RuntimeException('The uploaded file could not be read.');
        }

        $zip = new ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            throw new RuntimeException('The uploaded file is not a valid ZIP archive.');
        }

        $allNormalized = [];

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($name === false) {
                    continue;
                }

                $normalized = str_replace('\\', '/', $name);

                if (Str::startsWith($normalized, '/') || str_contains($normalized, '../') || str_contains($normalized, '..\\')) {
                    throw new RuntimeException('The archive contains an invalid or unsafe path: ' . $name);
                }

                if (Str::endsWith($normalized, '/')) {
                    continue;
                }

                $allNormalized[$i] = $normalized;
            }

            // Strip a single common top-level wrapper directory if present (e.g. from
            // macOS/Windows "zip folder" behavior producing blade-theme-starter-kit/pages/…).
            $prefix = $this->detectCommonPrefix(array_values($allNormalized));

            $found = [];
            foreach ($allNormalized as $normalized) {
                $relative = $prefix !== '' ? substr($normalized, strlen($prefix)) : $normalized;
                $lower    = strtolower($relative);
                $isBlade  = Str::endsWith($lower, '.blade.php');
                $extension = pathinfo($lower, PATHINFO_EXTENSION);

                if (!$isBlade && !in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                    throw new RuntimeException("Disallowed file type: {$normalized}");
                }

                if ($isBlade) {
                    $contents = (string) $zip->getFromName($normalized);
                    foreach (self::BLOCKED_PATTERNS as $pattern) {
                        if (stripos($contents, $pattern) !== false) {
                            throw new RuntimeException("Blocked pattern \"{$pattern}\" found in {$normalized}.");
                        }
                    }
                    $found[] = $relative;
                }
            }

            foreach (self::REQUIRED_FILES as $required) {
                if (!in_array($required, $found, true)) {
                    throw new RuntimeException("Required file missing: {$required}");
                }
            }

            $version = now()->format('YmdHis') . '-' . Str::random(6);
            $relativePath = $this->versionStoragePath($tenantId, $version);
            $absoluteExtractPath = storage_path('app/' . $relativePath);

            if (!is_dir($absoluteExtractPath)) {
                mkdir($absoluteExtractPath, 0755, true);
            }

            // Extract, stripping the wrapper prefix from each entry's destination path.
            foreach ($allNormalized as $originalPath) {
                $destRelative = $prefix !== '' ? substr($originalPath, strlen($prefix)) : $originalPath;
                $destAbsolute = $absoluteExtractPath . '/' . $destRelative;
                $destDir      = dirname($destAbsolute);

                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }

                file_put_contents($destAbsolute, $zip->getFromName($originalPath));
            }
        } finally {
            $zip->close();
        }

        return tenancy()->central(function () use ($tenantId, $version, $relativePath, $file) {
            $bladeTheme = BladeTheme::query()->create([
                'tenant_id' => $tenantId,
                'version' => $version,
                'storage_path' => $relativePath,
                'original_filename' => $file->getClientOriginalName(),
                'status' => BladeTheme::STATUS_PENDING,
                'is_active' => false,
                'uploaded_at' => now(),
            ]);

            HomeVariant::query()->create([
                'theme_slug'     => 'custom',
                'key'            => $version,
                'name'           => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'description'    => 'Uploaded blade theme',
                'is_default'     => false,
                'is_active'      => true,
                'blade_theme_id' => $bladeTheme->id,
            ]);

            return $bladeTheme;
        });
    }

    public function latestTheme(string $tenantId): ?BladeTheme
    {
        return tenancy()->central(fn() => BladeTheme::query()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->first());
    }

    public function themesForTenant(string $tenantId)
    {
        return tenancy()->central(fn() => BladeTheme::query()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->get());
    }

    public function activeBladeTheme(string $tenantId): ?BladeTheme
    {
        return tenancy()->central(fn() => BladeTheme::query()
            ->where('tenant_id', $tenantId)
            ->where('status', BladeTheme::STATUS_APPROVED)
            ->where('is_active', true)
            ->latest('id')
            ->first());
    }

    public function deactivate(string $tenantId): void
    {
        tenancy()->central(function () use ($tenantId) {
            BladeTheme::query()->where('tenant_id', $tenantId)->update(['is_active' => false]);
        });

        $liveLink = $this->liveViewsPath($tenantId);
        if (is_link($liveLink)) {
            @unlink($liveLink);
        }

        $this->syncThemeRow(activate: false);
    }

    /**
     * Called from TenantPanelService::activateTheme() when the vendor activates the
     * generic 'custom' theme card on Store → Themes. Activation of a specific Blade
     * theme version is no longer chosen from the Blade Theme upload page — the most
     * recently approved version is used. No-op if the tenant has no approved theme.
     */
    public function activateLatestApprovedForCurrentTenant(string $tenantId): void
    {
        $activated = tenancy()->central(function () use ($tenantId) {
            $theme = BladeTheme::query()
                ->where('tenant_id', $tenantId)
                ->where('status', BladeTheme::STATUS_APPROVED)
                ->latest('id')
                ->first();

            if (!$theme) {
                return false;
            }

            BladeTheme::query()->where('tenant_id', $tenantId)
            ->where('id', '!=', $theme->id)
            ->update(['is_active' => false]);
            $theme->update(['is_active' => true]);

            return true;
        });

        if ($activated) {
            $this->relinkLiveViews($tenantId);
        }
    }

    /** Called from TenantPanelService::activateTheme() when the vendor switches away from the 'custom' theme. */
    public function deactivateAllForCurrentTenant(string $tenantId): void
    {
        tenancy()->central(function () use ($tenantId) {
            BladeTheme::query()->where('tenant_id', $tenantId)->update(['is_active' => false]);
        });

        $liveLink = $this->liveViewsPath($tenantId);
        if (is_link($liveLink)) {
            @unlink($liveLink);
        }
    }

    /**
     * Upsert the tenant-local 'custom' Theme row so the vendor's approved Blade
     * theme shows up in Store → Appearance → Themes like any system theme, and
     * follows the same is_active radio-button semantics via
     * TenantPanelService::activateTheme() / deactivateTheme().
     *
     * Runs in the tenant DB context — the Theme model lives per-tenant, unlike
     * BladeTheme which is central. activate()/deactivate() are called from
     * BladeThemePage, a tenant-panel Livewire component already running
     * inside tenant context, so this is safe as written.
     */
    private function syncThemeRow(bool $activate): void
    {
        $theme = \App\Models\Tenant\Theme::query()->firstOrCreate(
            ['slug' => 'custom'],
            ['name' => 'Custom Theme', 'is_universal' => true, 'is_active' => false]
        );

        if ($activate) {
            app(TenantPanelService::class)->activateTheme($theme);
        } elseif ($theme->is_active) {
            $fallback = \App\Models\Tenant\Theme::query()
                ->where('slug', '!=', 'custom')
                ->where('is_universal', true)
                ->first();

            if ($fallback) {
                app(TenantPanelService::class)->activateTheme($fallback);
            }
        }
    }

    /** Admin-only: approve a pending theme. Does NOT activate it — the vendor still has to activate it from Store → Themes. */
    public function approve(int $themeId, string $reviewerName): void
    {
        $theme = BladeTheme::query()->findOrFail($themeId);

        $theme->update([
            'status' => BladeTheme::STATUS_APPROVED,
            'rejection_reason' => null,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewerName,
        ]);

        // If this version was already marked active (e.g. previously approved, then
        // re-submitted after a rejection), recreate the live-views symlink so the
        // storefront reflects the freshly approved files without the vendor needing
        // to deactivate and re-activate manually.
        // NOTE: relinkLiveViews uses storage_path() which is tenant-scoped, so we
        // must initialize tenant context before calling it from the admin (central) panel.
        if ($theme->is_active) {
            $tenant = \App\Models\Tenant::find($theme->tenant_id);
            if ($tenant) {
                tenancy()->initialize($tenant);
                try {
                    $this->relinkLiveViews((string) $theme->tenant_id);
                } finally {
                    tenancy()->end();
                }
            }
        }
    }

    public function reject(int $themeId, string $reason, string $reviewerName): void
    {
        BladeTheme::query()->findOrFail($themeId)->update([
            'status' => BladeTheme::STATUS_REJECTED,
            'is_active' => false,
            'rejection_reason' => $reason,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewerName,
        ]);
    }


    public function isLiveViewsPathHealthy(string $tenantId): bool
    {
        $path = $this->liveViewsPath($tenantId);
        return is_dir($path) && is_readable($path);
    }

    /**
     * Detects a single common top-level directory shared by all paths, returning
     * the prefix including its trailing slash, or '' if paths are already at root.
     *
     * @param  string[] $paths
     */
    private function detectCommonPrefix(array $paths): string
    {
        if (empty($paths)) {
            return '';
        }

        // Find the leading directory component of the first path.
        $firstSlash = strpos($paths[0], '/');
        if ($firstSlash === false) {
            return '';
        }

        $candidate = substr($paths[0], 0, $firstSlash + 1); // e.g. "blade-theme-starter-kit/"

        foreach ($paths as $path) {
            if (!str_starts_with($path, $candidate)) {
                return '';
            }
        }

        return $candidate;
    }

    /**
     * Absolute path to the active theme's extracted version directory, or null
     * when no approved+active theme exists. Avoids relying on the live-views
     * symlink so section discovery works even if the symlink is stale.
     */
    public function activeVersionBasePath(string $tenantId): ?string
    {
        $theme = $this->activeBladeTheme($tenantId);

        return $theme ? storage_path('app/' . $theme->storage_path) : null;
    }

    /**
     * Returns section keys discovered from pages/home/sections/*.blade.php.
     * Uses the active theme's version storage path directly; falls back to the
     * live-views symlink when no approved+active theme exists.
     * Returns [] when no theme is active or the sections directory doesn't exist.
     *
     * @return string[]
     */
    public function discoveredHomeSectionKeys(string $tenantId): array
    {
        $basePath = $this->activeVersionBasePath($tenantId) ?? $this->liveViewsPath($tenantId);
        $sectionsDir = $basePath . '/pages/home/sections';

        if (!is_dir($sectionsDir)) {
            return [];
        }

        $keys = [];
        foreach (glob($sectionsDir . '/*.blade.php') ?: [] as $file) {
            $keys[] = basename($file, '.blade.php');
        }
        sort($keys);

        return $keys;
    }

    /** Converts a snake_case section key into a human-readable label. */
    public static function keyToLabel(string $key): string
    {
        return ucwords(str_replace('_', ' ', $key));
    }

    /** Symlinks a blade theme's storage_path into the private path IdentifyTenantTheme looks for. */
    public function relinkLiveViews(string $tenantId, ?BladeTheme $theme = null): void
    {
        $theme ??= $this->activeBladeTheme($tenantId);

        $liveLink = $this->liveViewsPath($tenantId);

        if (is_link($liveLink) || is_dir($liveLink) || is_file($liveLink)) {
            @unlink($liveLink);
        }

        if (!$theme) {
            return;
        }

        if (!is_dir(dirname($liveLink))) {
            mkdir(dirname($liveLink), 0755, true);
        }

        $source = storage_path('app/' . $theme->storage_path);
        symlink($source, $liveLink);
    }

    /**
     * Activate a specific blade theme version identified by its HomeVariant.
     * Called from ThemesController::activateVariant() when theme slug is 'custom'.
     */
    public function activateSpecificVariant(string $tenantId, int $homeVariantId): void
    {
        $bladeTheme = tenancy()->central(function () use ($tenantId, $homeVariantId) {
            $variant = HomeVariant::query()->findOrFail($homeVariantId);

            if (!$variant->blade_theme_id) {
                throw new RuntimeException('This variant has no linked blade theme.');
            }

            $bladeTheme = BladeTheme::query()
                ->where('id', $variant->blade_theme_id)
                ->where('tenant_id', $tenantId)
                ->where('status', BladeTheme::STATUS_APPROVED)
                ->firstOrFail();
            BladeTheme::query()->where('tenant_id', $tenantId)
            ->where('id', '!=', $bladeTheme->id)->update(['is_active' => false]);
            $bladeTheme->update(['is_active' => 1]);

            return $bladeTheme;
        });
        $this->relinkLiveViews($tenantId, $bladeTheme);
        $this->seedPageBuilderSectionsForVariant($tenantId, $bladeTheme, $homeVariantId);
        $this->syncThemeRow(activate: true);
    }

    /**
     * Discover sections from pages/home/sections/ in a specific blade theme version
     * and upsert TenantPageSection rows for the given HomeVariant.
     */
    public function seedPageBuilderSectionsForVariant(string $tenantId, BladeTheme $bladeTheme, int $homeVariantId): void
    {
        $sectionsDir = storage_path('app/' . $bladeTheme->storage_path) . '/pages/home/sections';

        if (!is_dir($sectionsDir)) {
            return;
        }

        $keys = [];
        foreach (glob($sectionsDir . '/*.blade.php') ?: [] as $file) {
            $keys[] = basename($file, '.blade.php');
        }
        sort($keys);

        $theme = Theme::query()->where('slug', 'custom')->first();
        if (!$theme) {
            return;
        }

        foreach ($keys as $i => $key) {
            TenantPageSection::query()->updateOrCreate(
                [
                    'theme_id'        => $theme->id,
                    'home_variant_id' => $homeVariantId,
                    'page'            => 'home',
                    'section_key'     => $key,
                ],
                [
                    'sort_order' => $i,
                    'is_visible' => true,
                ]
            );
        }
    }
}
