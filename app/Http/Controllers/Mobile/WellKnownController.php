<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/** Deep-link verification files, configured via MOBILE_ANDROID_PACKAGE / MOBILE_ANDROID_SHA256 / MOBILE_IOS_APP_ID. */
class WellKnownController extends Controller
{
    public function assetLinks(): JsonResponse
    {
        $package = config('mobile.android_package');
        $fingerprints = array_values(array_filter(array_map('trim', explode(',', (string) config('mobile.android_sha256')))));

        if (!$package || !$fingerprints) {
            return response()->json([]);
        }

        return response()->json([[
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target' => ['namespace' => 'android_app', 'package_name' => $package, 'sha256_cert_fingerprints' => $fingerprints],
        ]]);
    }

    public function appleAssociation(): JsonResponse
    {
        $appId = config('mobile.ios_app_id'); // "<TeamID>.<BundleID>"
        $details = $appId ? [['appIDs' => [$appId], 'components' => [['/' => '/*']]]] : [];

        return response()->json([
            'applinks' => ['details' => $details],
            'webcredentials' => ['apps' => $appId ? [$appId] : []],
        ]);
    }
}
