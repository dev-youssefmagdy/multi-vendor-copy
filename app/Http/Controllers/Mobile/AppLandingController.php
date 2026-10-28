<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

/** "Open in app / Install" fallback on tenant domains. Preserves ?path= and the store identity (host). */
class AppLandingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $host = strtolower($request->getHost());
        // Only same-site relative paths; never an open redirect.
        $path = (string) $request->query('path', '/');
        $path = '/'.ltrim($path, '/');
        if (str_starts_with($path, '//') || str_contains($path, '\\') || preg_match('/[\x00-\x1f]/', $path)) {
            $path = '/';
        }
        $scheme = (string) config('mobile.deep_link_scheme', 'storeapp');

        return view('storefront.app-landing', [
            'host' => $host,
            'webUrl' => $request->getSchemeAndHttpHost().$path,
            // Custom scheme carries the store identity as host: storeapp://<store-host>/<path>
            'appUrl' => $scheme.'://'.$host.$path,
            'playUrl' => config('mobile.play_store_url'),
            'iosUrl' => config('mobile.app_store_url'),
            'storeCode' => (string) (function_exists('tenant') && tenant() ? tenant('id') : ''),
        ]);
    }
}
