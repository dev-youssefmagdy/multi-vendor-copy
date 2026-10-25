<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Symfony\Component\HttpFoundation\Response;

/**
 * The default /broadcasting/auth route (registered on the central router)
 * never initializes tenancy, so private channels scoped to a tenant
 * subdomain authorize here instead.
 */
class BroadcastAuthController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $result = Broadcast::auth($request);

        // Pusher/Reverb broadcasters return an array payload, not a Response.
        return $result instanceof Response ? $result : response()->json($result);
    }
}
