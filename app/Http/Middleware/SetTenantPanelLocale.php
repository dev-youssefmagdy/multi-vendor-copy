<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Tenant\PanelLocale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the vendor panel language (chosen in the panel header) to every
 * /admin request. Runs after the global SetLocale (web group), so it wins.
 */
class SetTenantPanelLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie(PanelLocale::COOKIE);

        if (PanelLocale::isSupported($locale)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
