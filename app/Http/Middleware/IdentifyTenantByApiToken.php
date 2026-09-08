<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifies the tenant for stateless /api/v1/* requests via a bearer token.
 *
 * The token is the tenant's id, sent as:
 *
 *   Authorization: Bearer {tenant_id}
 *
 * This lets external clients call the storefront JSON APIs directly on the
 * central domain, without going through domain- or slug-based tenancy
 * resolution. On success it initializes tenancy so downstream controllers
 * (CartController, FavoriteController, ...) behave exactly as they do on the
 * tenant's own storefront.
 */
class IdentifyTenantByApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!filled($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Missing API token. Send it as: Authorization: Bearer {tenant_id}',
            ], 401);
        }

        /** @var Tenant|null $tenant */
        $tenant = Tenant::find($token);

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API token.',
            ], 401);
        }

        if (!tenancy()->initialized || tenancy()->tenant?->getKey() !== $tenant->getKey()) {
            tenancy()->initialize($tenant);
        }

        return $next($request);
    }
}
