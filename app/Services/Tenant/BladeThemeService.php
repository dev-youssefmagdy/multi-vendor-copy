<?php

namespace App\Services\Tenant;

use App\Models\BladeTheme;
use App\Models\Tenant as TenantModel;
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
 * never live until an admin explicitly reviews it and calls
 * approveAndActivate() — uploads always land as `pending` and are never
 * auto-approved or auto-activated.
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
        '<?php', '<?=', 'system(', 'exec(', 'shell_exec(', 'passthru(', 'eval(',
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
        $tenantId = is_string($tenant) ? $tenant : $tenant->getTenantKey();

        $absolutePath = $file->getRealPath();
        if (!$absolutePath) {
            throw new RuntimeException('The uploaded file could not be read.');
        }

        $zip = new ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            throw new RuntimeException('The uploaded file is not a valid ZIP archive.');
        }

        $found = [];

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

                $lower = strtolower($normalized);
                $isBlade = Str::endsWith($lower, '.blade.php');
                $extension = pathinfo($lower, PATHINFO_EXTENSION);

                if (!$isBlade && !in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                    throw new RuntimeException("Disallowed file type: {$name}");
                }

                if ($isBlade) {
                    $contents = (string) $zip->getFromIndex($i);
                    foreach (self::BLOCKED_PATTERNS as $pattern) {
                        if (stripos($contents, $pattern) !== false) {
                            throw new RuntimeException("Blocked pattern \"{$pattern}\" found in {$name}.");
                        }
                    }
                    $found[] = $normalized;
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

            $zip->extractTo($absoluteExtractPath);
        } finally {
            $zip->close();
        }

        return tenancy()->central(function () use ($tenantId, $version, $relativePath, $file) {
            return BladeTheme::query()->create([
                'tenant_id' => $tenantId,
                'version' => $version,
                'storage_path' => $relativePath,
                'original_filename' => $file->getClientOriginalName(),
                'status' => BladeTheme::STATUS_PENDING,
                'is_active' => false,
                'uploaded_at' => now(),
            ]);
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

    /** Vendor-triggered: only an already-approved theme may be activated. Never bypasses admin review. */
    public function activate(string $tenantId, int $themeId): void
    {
        tenancy()->central(function () use ($tenantId, $themeId) {
            $theme = BladeTheme::query()
                ->where('tenant_id', $tenantId)
                ->where('status', BladeTheme::STATUS_APPROVED)
                ->findOrFail($themeId);

            BladeTheme::query()->where('tenant_id', $tenantId)->update(['is_active' => false]);
            $theme->update(['is_active' => true]);
        });

        $this->relinkLiveViews($tenantId);
        $this->syncThemeRow(activate: true);
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

    /** Admin-only: approve a pending theme. Does NOT activate it — the vendor still has to activate() it. */
    public function approve(int $themeId, string $reviewerName): void
    {
        BladeTheme::query()->findOrFail($themeId)->update([
            'status' => BladeTheme::STATUS_APPROVED,
            'rejection_reason' => null,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewerName,
        ]);
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

    /** Symlinks the active theme's storage_path into the private path IdentifyTenantTheme looks for. */
    private function relinkLiveViews(string $tenantId): void
    {
        $theme = $this->activeBladeTheme($tenantId);

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
}
