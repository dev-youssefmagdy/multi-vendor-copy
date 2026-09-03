<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantOwnerAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $owner = auth()->guard('tenant_owner')->user();

        if (!$owner) {
            return redirect()->route('owner.login');
        }

        if (!$owner->selected_tenant_id) {
            return redirect()->route('owner.select-tenant');
        }

        return $next($request);
    }
}
