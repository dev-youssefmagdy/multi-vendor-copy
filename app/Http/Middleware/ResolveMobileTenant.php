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
        $id = trim((string) ($request->route('tenant') ?? $request->header('X-Tenant', '')));

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

        if (true) {
            tenancy()->initialize($tenant);
        }

        $request->attributes->set('mobile_tenant', $tenant);
        $this->applyContext($request);

        return $next($request);
    }

    /**
     * Mirrors ApplyStorefrontSessionContext for a stateless client: the storefront services read
     * country / currency / language from the session store, so fill it (in memory only) from the
     * X-Country, X-Currency and Accept-Language headers. Stale keys from a previous request are cleared.
     */
    private function applyContext(Request $request): void
    {
        // Stateless API: the session store is used as an in-memory bag only (never started/saved).
        $request->setLaravelSession(app('session.store'));

        foreach (['storefront_cart', 'storefront_coupon', 'storefront_country_id', 'storefront_currency', 'storefront_language', 'storefront_pending_payment', 'storefront_companion_order_uuid'] as $key) {
            session()->forget($key);
        }

        if ($country = trim((string) $request->header('X-Country', ''))) {
            $id = ctype_digit($country)
                ? \App\Models\Country::query()->whereKey((int) $country)->value('id')
                : \App\Models\Country::query()->where('iso2', strtoupper($country))->value('id');
            if ($id) {
                session(['storefront_country_id' => (int) $id]);
            }
        }

        if ($currency = strtoupper(trim((string) $request->header('X-Currency', '')))) {
            if (\App\Models\Tenant\Currency::query()->where('is_active', true)->where('code', $currency)->exists()) {
                session(['storefront_currency' => $currency]);
            }
        }

        $lang = strtolower(substr(trim((string) $request->header('Accept-Language', '')), 0, 2));
        if ($lang !== '' && \App\Models\Tenant\Language::query()->where('is_active', true)->where('code', $lang)->exists()) {
            session(['storefront_language' => $lang]);
            app()->setLocale($lang);
        }
    }

    private function fail(string $message, string $code, int $status): Response
    {
        return response()->json(['success' => false, 'message' => $message, 'code' => $code], $status);
    }
}
