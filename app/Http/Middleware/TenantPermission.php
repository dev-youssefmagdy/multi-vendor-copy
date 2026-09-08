<?php

namespace App\Http\Middleware;

use App\Models\Tenant\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response;

class TenantPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = auth('tenant')->user();

        if (! $user instanceof AdminUser) {
            return redirect()->route('tenant.login');
        }

        if ($permissions === []) {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        if ($user->hasPermission('dashboard.view') && $request->route()?->getName() !== 'tenant.dashboard') {
            return redirect()
                ->route('tenant.dashboard')
                ->with('status', 'You do not have permission to access that section.')
                ->with('status_type', 'error');
        }

        throw new AccessDeniedHttpException('You do not have permission to access that section.');
    }
}
