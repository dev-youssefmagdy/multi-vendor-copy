<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileAppLinks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Deep-link verification files. On a tenant domain (subdomain or custom domain): that tenant's white-label app
 * (central admin > Tenants > Edit > Mobile App) plus the generic platform app; on the central domain (or an
 * unknown host): the platform app only (MOBILE_ANDROID_PACKAGE / MOBILE_ANDROID_SHA256 / MOBILE_IOS_APP_ID).
 */
class WellKnownController extends Controller
{
    public function assetLinks(Request $request, MobileAppLinks $links): JsonResponse
    {
        return response()->json($links->assetLinks($links->tenantForHost($request->getHost())));
    }

    public function appleAssociation(Request $request, MobileAppLinks $links): JsonResponse
    {
        $appIds = $links->iosAppIds($links->tenantForHost($request->getHost()));
        $details = $appIds ? [['appIDs' => $appIds, 'components' => [['/' => '/*']]]] : [];

        return response()->json([
            'applinks' => ['details' => $details],
            'webcredentials' => ['apps' => $appIds],
        ]);
    }
}
