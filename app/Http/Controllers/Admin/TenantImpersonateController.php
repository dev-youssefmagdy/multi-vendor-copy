<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Middleware\AdminAuth;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Generates a short-lived impersonation token that allows a central admin to
 * log into a tenant's admin panel without knowing the tenant's credentials.
 *
 * Flow:
 *   1. Central admin visits  GET /admin/tenants/{tenantId}/impersonate
 *   2. This controller stores a one-time token in the cache (TTL 90 s)
 *   3. The admin is redirected to  {tenant-domain}/admin/impersonate/{token}
 *   4. TenantImpersonateController::accept() on the tenant side validates the
 *      token, logs the admin in as the tenant's super-admin, and redirects to
 *      the tenant dashboard.
 *
 * Security notes:
 *   - Tokens are single-use (consumed on first use).
 *   - Tokens expire after 90 seconds.
 *   - The route is protected by AdminAuth middleware (central super-admin only).
 *   - The token is a cryptographically random 64-character hex string.
 */
class TenantImpersonateController
{
    public function generate(Request $request, string $tenantId): RedirectResponse
    {
        /** @var \App\Models\AdminUser $admin */
        $admin = $request->user('admin');

        /** @var Tenant|null $tenant */
        $tenant = Tenant::query()->with('domains')->find($tenantId);

        if (!$tenant) {
            abort(404, 'Tenant not found.');
        }

        // Generate a cryptographically random single-use token.
        $token = Str::random(64);

        Cache::put(
            "tenant_impersonate:{$token}",
            [
                'tenant_id' => $tenant->id,
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
            ],
            now()->addSeconds(90),
        );

        // Build the target URL on the tenant's canonical domain.
        // Prefer the subdomain entry (slug.centralDomain); fall back to the
        // first domain record or abort if none is configured.
        $centralDomain = collect(config('tenancy.central_domains', []))
            ->first(fn($d) => !in_array($d, ['127.0.0.1', 'localhost']));

        $subdomainHost = $centralDomain && filled($tenant->slug)
            ? $tenant->slug . '.' . $centralDomain
            : null;

        $targetHost = $subdomainHost
            ?? $tenant->domains->first()?->domain;

        if (!$targetHost) {
            return back()->with('error', 'This tenant has no domain configured yet.');
        }

        $scheme = $request->isSecure() ? 'https' : 'http';
        $targetUrl = "{$scheme}://{$targetHost}/admin/impersonate/{$token}";

        return redirect()->away($targetUrl);
    }
}
