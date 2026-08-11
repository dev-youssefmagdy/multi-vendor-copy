<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\Tenant\PaymentGateway;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevents access to the tenant storefront when the tenant has been blocked
 * from using the platform's shared payment gateway due to exceeding the
 * configured sales threshold (notified_sales_amount_block).
 *
 * The tenant is automatically unblocked when they configure their own payment
 * gateway credentials (at least one active gateway with use_own = true).
 */
class CheckTenantGatewayBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if (!$tenant instanceof Tenant) {
            return $next($request);
        }

        $data = (array) ($tenant->data ?? []);

        if (!($data['gateway_payment_blocked'] ?? false)) {
            return $next($request);
        }

        // Re-check live: if the tenant has since configured their own gateway,
        // clear the block flag and let them through immediately.
        $hasOwnGateway = PaymentGateway::query()
            ->where('is_active', true)
            ->where('use_own', true)
            ->exists();

        if ($hasOwnGateway) {
            // Auto-clear the flag so subsequent requests skip this branch.
            $data['gateway_payment_blocked'] = false;
            tenancy()->central(function () use ($tenant, $data) {
                $tenant->update(['data' => $data]);
            });

            return $next($request);
        }

        // Tenant is still blocked — return a 503 page.
        return response()->view('errors.gateway-blocked', [
            'tenant' => $tenant,
        ], Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
