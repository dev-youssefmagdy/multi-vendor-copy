<?php

namespace App\Http\Middleware;

use App\Helpers\TenantNavigation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks the public storefront if the tenant has not yet clicked
 * "Store Ready for Launch" in the post-registration onboarding wizard.
 * Shows a "Configure website first" splash page instead.
 *
 * Apply only to public-facing storefront routes, NOT admin/panel routes.
 */
class StoreLaunchGate
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if (!$tenant || (bool) ($tenant->launch_ready ?? false)) {
            return $next($request);
        }

        // Auto-pass: all 8 setup prerequisites are satisfied — treat the store
        // as launched without requiring the wizard flag to have been set.
        if (TenantNavigation::allSetupStepsDone()) {
            return $next($request);
        }

        $adminLoginUrl = 'http://' . $request->getHost() . '/admin/login';

        return response()->view('errors.store-not-launched', [
            'adminLoginUrl' => $adminLoginUrl,
        ], 200);
    }
}
