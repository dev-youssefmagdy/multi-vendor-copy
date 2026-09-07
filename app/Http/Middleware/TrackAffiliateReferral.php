<?php

namespace App\Http\Middleware;

use App\Models\Affiliate;
use App\Services\AffiliateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackAffiliateReferral
{
    public function __construct(protected AffiliateService $affiliateService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $code = $request->query('ref');

        if ($code && !$request->hasCookie(AffiliateService::COOKIE_NAME)) {
            $affiliate = Affiliate::query()
                ->where('code', strtoupper((string) $code))
                ->where('status', 'active')
                ->first();

            if ($affiliate) {
                $this->affiliateService->trackClick($affiliate, $request);
            }
        }

        return $next($request);
    }
}
