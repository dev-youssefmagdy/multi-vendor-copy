<?php

namespace App\Http\Middleware;

use Closure;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class InitializeTenancyByDomainForLivewire
{
    public function __construct(protected InitializeTenancyByDomain $initializeTenancyByDomain)
    {
    }

    public function handle($request, Closure $next)
    {
        if (in_array($request->getHost(), config('tenancy.central_domains'), true)) {
            return $next($request);
        }

        return $this->initializeTenancyByDomain->handle($request, $next);
    }
}
