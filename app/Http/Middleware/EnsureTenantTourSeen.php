<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant\AdminUser;
use App\Services\Tenant\ComplianceService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantTourSeen
{
    public function __construct(private readonly ComplianceService $compliance)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('tenant.onboarding') || $request->expectsJson() || $request->method() !== 'GET') {
            return $next($request);
        }

        if ($this->compliance->compliancePagesExist() && !$this->compliance->hasAcceptedCurrentCompliance()) {
            return $next($request);
        }

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('tenant')->user();

        if ($admin && $admin->tour_seen_at === null) {
            return redirect()->route('tenant.onboarding', ['tab' => 'tour']);
        }

        return $next($request);
    }
}
