<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileBootstrapBuilder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** GET /bootstrap with ETag / 304 on config_version. */
class BootstrapController extends Controller
{
    public function __invoke(Request $request, MobileBootstrapBuilder $builder): Response
    {
        $data = $builder->build($request->header('X-App-Version'));
        $etag = '"'.$data['config_version'].'-'.(int) $data['status']['update_required'].(int) $data['status']['maintenance'].'"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304)->header('ETag', $etag);
        }

        return response()->json(['success' => true, 'data' => $data])->header('ETag', $etag);
    }
}
