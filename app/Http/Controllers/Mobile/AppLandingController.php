<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileAppLinks;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

/** "Open in app / Install" fallback on tenant domains. Preserves ?path= and the store identity (host). */
class AppLandingController extends Controller
{
    public function __invoke(Request $request, MobileAppLinks $links): View
    {
        $host = strtolower($request->getHost());
        // Only same-site relative paths; never an open redirect.
        $path = (string) $request->query('path', '/');
        $path = '/'.ltrim($path, '/');
        if (str_starts_with($path, '//') || str_contains($path, '\\') || preg_match('/[\x00-\x1f]/', $path)) {
            $path = '/';
        }

        $tenant = function_exists('tenant') ? tenant() : null;
        $app = $links->appFor($tenant);
        $platformScheme = $links->platformScheme();
        // An app that announces itself (X-App-Scheme, validated) gets its own scheme. A browser visit gets the
        // store's own white-label app when it has one (the install buttons below point to that app too),
        // otherwise the generic platform app.
        $scheme = $links->requestScheme($request, $tenant) ?? $links->tenantScheme($tenant) ?? $platformScheme;

        return view('storefront.app-landing', [
            'host' => $host,
            'webUrl' => $request->getSchemeAndHttpHost().$path,
            // Custom scheme carries the store identity as host: <scheme>://<store-host>/<path>
            'appUrl' => $scheme.'://'.$host.$path,
            // Secondary "open in the generic platform app" link when the primary one targets the store's own app.
            'platformAppUrl' => $scheme !== $platformScheme ? $platformScheme.'://'.$host.$path : null,
            'playUrl' => $app?->play_store_url ?: config('mobile.play_store_url'),
            'iosUrl' => $app?->app_store_url ?: config('mobile.app_store_url'),
            'storeCode' => (string) ($tenant ? $tenant->getKey() : ''),
        ]);
    }
}
