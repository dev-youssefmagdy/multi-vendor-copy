<?php

namespace App\Http\Middleware;

use App\Helpers\TenantNavigation;
use App\Models\Tenant\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locks the whole vendor panel behind the "Quick Store Setup" checklist:
 * until every item is done, every page except the onboarding page itself
 * is unreachable directly — it must be opened via a setup action button
 * (which appends ?from=onboarding). Once the checklist is 100% complete,
 * navigation is unrestricted again.
 *
 * Applied to the authenticated tenant admin route group in routes/tenant.php.
 */
class EnforceOnboardingSetup
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('tenant.onboarding')) {
            return $next($request);
        }

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('tenant')->user();

        if (!$admin) {
            return $next($request);
        }

        if (TenantNavigation::onboardingSetupComplete()) {
            return $next($request);
        }

        if ($request->query('from') === 'onboarding') {
            return $next($request);
        }

        return redirect()
            ->route('tenant.onboarding', ['tab' => 'setup'])
            ->with('setup_warning', 'Please complete your store setup before accessing other pages.');
    }
}
