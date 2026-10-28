<?php

declare(strict_types=1);

namespace App\Http\Middleware;

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

        return $next($request);
    }
}
