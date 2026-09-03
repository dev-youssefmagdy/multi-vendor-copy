<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = auth('admin')->user();

        if (!$user) {
            return redirect()->route('admin.login');
        }

        if ($permissions === []) {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        if ($user->hasPermission('dashboard.view') && $request->route()?->getName() !== 'admin.dashboard') {
            return redirect()
                ->route('admin.dashboard')
                ->with('status', 'You do not have permission to access that section.');
        }

        throw new AccessDeniedHttpException('You do not have permission to access that section.');
    }
}
