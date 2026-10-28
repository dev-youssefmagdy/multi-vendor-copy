<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant\MobileCart;
use App\Services\Mobile\MobileTokenService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mobile API: authenticates the Bearer mobile token against the CURRENT tenant's
 * database (so a token minted for another tenant can never match) and binds the
 * customer to the `storefront` guard so existing controllers keep working.
 * Must run after ResolveMobileTenant.
 */
class AuthenticateMobileToken
{
    public function __construct(private readonly MobileTokenService $tokens)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->tokens->verify($request->bearerToken());
        $customer = $token?->customer;

        if (!$token || !$customer || !$customer->active) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.', 'code' => 'unauthenticated'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->saveQuietly();

        Auth::guard('storefront')->setUser($customer);
        Auth::shouldUse('storefront');
        $request->setUserResolver(fn () => $customer);
        $request->attributes->set('mobile_token', $token);

        // Hydrate the customer's server-side cart into the (in-memory) session keys the existing
        // cart / coupon / checkout services read, then persist whatever they changed.
        $tenantModel = tenancy()->tenant;
        $cart = MobileCart::query()->where('customer_id', $customer->id)->first();
        session(['storefront_cart' => (array) ($cart?->items ?? [])]);
        $cart?->coupon_code ? session(['storefront_coupon' => $cart->coupon_code]) : session()->forget('storefront_coupon');

        $response = $next($request);

        // Some services run work in the central context; make sure we write back to the tenant DB.
        if ($tenantModel && (!tenancy()->initialized || tenancy()->tenant?->getKey() !== $tenantModel->getKey())) {
            tenancy()->initialize($tenantModel);
        }

        $items = (array) session('storefront_cart', []);
        $coupon = session('storefront_coupon');
        if ($cart || $items !== [] || $coupon) {
            MobileCart::query()->updateOrCreate(
                ['customer_id' => $customer->id],
                ['items' => $items, 'coupon_code' => $coupon],
            );
        }

        return $response;
    }
}
