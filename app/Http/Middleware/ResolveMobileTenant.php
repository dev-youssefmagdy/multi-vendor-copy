<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mobile API: initialises tenancy from the `X-Tenant: <tenant_id>` header.
 * Rejects unknown tenants (404) and non-active stores (403, code store_suspended).
 */
class ResolveMobileTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = trim((string) $request->header('X-Tenant', ''));

        if ($id === '') {
            return $this->fail('Missing X-Tenant header.', 'tenant_missing', 400);
        }

        /** @var Tenant|null $tenant */
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return $this->fail('Store not found.', 'tenant_not_found', 404);
        }

        if ($tenant->status !== TenantStatus::Active) {
            return $this->fail('This store is currently unavailable.', 'store_suspended', 403);
        }

        if (!tenancy()->initialized || tenancy()->tenant?->getKey() !== $tenant->getKey()) {
            tenancy()->initialize($tenant);
        }

        $request->attributes->set('mobile_tenant', $tenant);

        return $next($request);
    }

    private function fail(string $message, string $code, int $status): Response
    {
        return response()->json(['success' => false, 'message' => $message, 'code' => $code], $status);
    }
}
