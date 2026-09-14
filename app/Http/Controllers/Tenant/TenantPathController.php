<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantPathController extends Controller
{
    /**
     * Livewire update requests are intercepted by the Livewire mechanism
     * before reaching here; this handler only exists so the middleware
     * (which initializes tenancy) runs first.
     */
    public function livewireUpdate(): never
    {
        abort(404);
    }

    /**
     * The full admin panel is only available on the subdomain/custom domain.
     * Redirect path-based /admin requests to the canonical tenant URL.
     */
    public function adminRedirect(Request $request, string $any = ''): RedirectResponse
    {
        /** @var \App\Models\Tenant|null $tenant */
        $tenant = tenancy()->tenant;
        $domain = $tenant?->domains->first()?->domain;

        if (!$domain) {
            abort(404);
        }

        $scheme = $request->isSecure() ? 'https' : 'http';

        return redirect("{$scheme}://{$domain}/admin{$any}", 301);
    }
}
