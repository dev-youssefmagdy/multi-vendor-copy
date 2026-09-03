<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifies the tenant by the {tenant} route parameter (slug value) instead
 * of by HTTP hostname.  Used for path-based access on the central domain:
 *
 *   nogrgr.com/{slug}/...   →  InitializeTenancyBySlug  →  tenant DB
 *
 * The middleware is also registered as a persistent Livewire middleware so it
 * re-runs on every Livewire update request that originates from a path-based
 * page.
 *
 * It is idempotent: if tenancy is already initialised for the same tenant it
 * skips re-initialisation to avoid redundant bootstrapper calls.
 */
class InitializeTenancyBySlug
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('tenant');

        // Not a path-based route – let the request pass through unchanged.
        if (!filled($slug)) {
            return $next($request);
        }

        // Idempotency: same tenant already initialised for this request.
        if (
            tenancy()->initialized &&
            tenancy()->tenant instanceof Tenant &&
            tenancy()->tenant->slug === $slug
        ) {
            return $next($request);
        }


        /** @var Tenant|null $tenant */
        $tenant = Tenant::query()->whereJsonContains('data->slug', $slug)->first();

        if (!$tenant) {
            abort(404, "Store not found: {$slug}");
        }

        // Store the slug in runtime config so theme layouts can build the
        // correct Livewire update URI (see tenant_path.php storefront layout).
        config(['tenancy.path_tenant_slug' => $slug]);

        tenancy()->initialize($tenant);

        return $next($request);
    }
}
