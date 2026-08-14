<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Initializes tenancy for the dedicated "preview" tenant, regardless of the
 * requesting domain. Backs the central `/preview` route used by the tenant
 * panel "Preview" button and the admin Templates page.
 */
class InitializeTenancyForPreview
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            tenancy()->initialized
            && tenancy()->tenant instanceof Tenant
            && tenancy()->tenant->slug === 'preview'
        ) {
            return $next($request);
        }

        $tenant = Tenant::query()->whereJsonContains('data->slug', 'preview')->first();

        if (!$tenant) {
            abort(404, 'Preview tenant is not configured. Run: php artisan db:seed --class=TestPreviewTenantSeeder');
        }

        tenancy()->initialize($tenant);

        return $next($request);
    }
}
