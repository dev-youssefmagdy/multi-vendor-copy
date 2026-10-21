<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class InitializeTenancyByDomainForLivewire
{
    public function __construct(protected InitializeTenancyByDomain $initializeTenancyByDomain)
    {
    }

    public function handle($request, Closure $next)
    {
        if (in_array($request->getHost(), config('tenancy.central_domains'), true)) {
            // Components on the central-domain /preview page belong to the preview tenant.
            $referer = (string) $request->headers->get('referer');

            if (parse_url($referer, PHP_URL_PATH) === '/preview') {
                return app(InitializeTenancyForPreview::class)->handle($request, function ($request) use ($referer, $next) {
                    // The update request has no ?theme=/homepage_variant=; replay the page's.
                    app(ForcePreviewTemplate::class)->apply(Request::create($referer));

                    return $next($request);
                });
            }

            return $next($request);
        }

        return $this->initializeTenancyByDomain->handle($request, $next);
    }
}
