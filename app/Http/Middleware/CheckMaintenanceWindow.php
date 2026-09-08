<?php

namespace App\Http\Middleware;

use App\Models\MaintenanceWindow;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceWindow
{
    public function handle(Request $request, Closure $next): Response
    {
        // Never intercept admin routes, the maintenance page itself, or the locale switcher.
        if (
            $request->is('admin', 'admin/*') ||
            $request->routeIs('website.maintenance') ||
            $request->routeIs('locale.switch')
        ) {
            return $next($request);
        }

        $window = MaintenanceWindow::query()->latest('updated_at')->first();

        if ($window && $window->is_active) {
            $now = now();

            $started = $window->starts_at === null || $now->greaterThanOrEqualTo($window->starts_at);
            $notEnded = $window->ends_at === null || $now->lessThanOrEqualTo($window->ends_at);

            if ($started && $notEnded) {
                return redirect()->route('website.maintenance');
            }
        }

        return $next($request);
    }
}
