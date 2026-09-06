<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Tenant\BladeThemeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\View\Factory as ViewFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * If the current tenant has an active, admin-approved Blade theme, prepend
 * its private storage path to the Laravel view finder so view resolution
 * checks the tenant's uploaded theme first, falling back to the system
 * theme for any file the vendor didn't override.
 *
 * Tenant theme files live at storage/app/tenants/{tenant_id}/theme/views/
 * (a symlink to the approved BladeTheme's storage_path, maintained by
 * BladeThemeService::activate()). This path is never web-accessible — files
 * are only ever rendered through Laravel's Blade compiler.
 */
class IdentifyTenantTheme
{
    public function __construct(private readonly ViewFactory $views) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = function_exists('tenant') ? tenant() : null;

        if ($tenant) {
            $themePath = app(BladeThemeService::class)->liveViewsPath((string) $tenant->getTenantKey());

            if (is_dir($themePath)) {
                $this->views->getFinder()->prependLocation($themePath);
            }
        }

        return $next($request);
    }
}
