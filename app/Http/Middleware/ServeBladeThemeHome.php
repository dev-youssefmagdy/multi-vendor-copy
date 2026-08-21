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
 * intercepts the storefront home route, and only when no static
 * CustomTemplate is already active for this tenant (that check happens
 * earlier in the middleware chain, in ServeCustomTemplateHome).
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
        $active = $service->activeBladeTheme((string) $tenant->getTenantKey());

        if (!$active) {
            return $next($request);
        }

        return app(StorefrontHomeController::class)->__invoke(app(\App\Repositories\Tenant\StorefrontRepository::class));
    }
}
