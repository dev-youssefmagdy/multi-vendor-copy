<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Controllers\Tenant\CustomTemplateController;
use App\Services\Tenant\CustomTemplateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * If the current tenant has an approved & activated custom storefront
 * template, serve its uploaded index.html (with tracking pixels injected)
 * instead of letting the request fall through to the Blade-rendered theme
 * home page. Only intercepts the storefront home route.
 */
class ServeCustomTemplateHome
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = function_exists('tenant') ? tenant() : null;

        if (!$tenant) {
            return $next($request);
        }

        $service = app(CustomTemplateService::class);
        $active = $service->activeTemplate((string) $tenant->getTenantKey());

        if (!$active) {
            return $next($request);
        }

        return app(CustomTemplateController::class)->live($request, $service);
    }
}
