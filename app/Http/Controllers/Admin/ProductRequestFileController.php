<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductRequest;
use App\Support\ProductRequestFiles;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * PANELS-UPDATES-PRODUCT-REQUESTS: lets admins open attachments a tenant uploaded with a product request
 * (they live in the tenant's own storage, outside the public symlink).
 */
final class ProductRequestFileController extends Controller
{
    public function show(Request $request, int $requestId): BinaryFileResponse
    {
        $productRequest = ProductRequest::with('messages')->findOrFail($requestId);
        $path = (string) $request->query('path', '');

        // Only files that are actually attached to this request (or one of its tenant messages).
        $known = collect($productRequest->attachments ?? [])
            ->merge($productRequest->messages->where('sender_type', 'tenant')->flatMap(fn ($m) => $m->attachments ?? []))
            ->all();

        abort_unless(in_array($path, $known, true), 404);

        $file = ProductRequestFiles::tenantFilePath((string) $productRequest->tenant_id, $path);
        abort_if($file === null, 404);

        return response()->file($file);
    }
}
