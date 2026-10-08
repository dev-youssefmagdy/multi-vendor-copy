<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Controllers\Tenant\StorefrontHomeController;
use App\Services\Tenant\BladeThemeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * If the current tenant has an active, admin-approved Blade theme, serve the
 * storefront home page through StorefrontHomeController (which renders the
 * vendor's `pages.home.index` view, resolved via the prepended theme path)
 * instead of falling through to the default Livewire HomePage. Only
 * intercepts the storefront home route.
 */
class ServeBladeThemeHome
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = function_exists('tenant') ? tenant() : null;

        if (!$tenant) {
            return $next($request);
        }

        $service = app(BladeThemeService::class);
        $tenantId = (string) $tenant->getTenantKey();
        $active = $service->activeBladeTheme($tenantId);

        if (!$active) {
            return $next($request);
        }

        // Guard: DB says active but the symlink may be stale (e.g. after a re-deploy).
        // Attempt self-heal; if it still fails, fall through to the default Livewire home
        // rather than throwing a "View not found" 500.
        if (!$service->isLiveViewsPathHealthy($tenantId)) {
            try {
                $service->relinkLiveViews($tenantId);
            } catch (\Throwable) {
                return $next($request);
            }

            if (!$service->isLiveViewsPathHealthy($tenantId)) {
                return $next($request);
            }
        }

        $view = app(StorefrontHomeController::class)->__invoke(app(\App\Repositories\Tenant\StorefrontRepository::class));

        return response($view);
    }
}
