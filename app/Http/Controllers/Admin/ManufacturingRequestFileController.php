<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManufacturingRequest;
use App\Support\ProductRequestFiles;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * PANELS-UPDATES-MANUFACTURING: lets admins open the reference images a tenant attached to a manufacturing request
 * (they live in the tenant's own storage, outside the public symlink).
 */
final class ManufacturingRequestFileController extends Controller
{
    public function show(Request $request, int $id): BinaryFileResponse
    {
        $manufacturingRequest = ManufacturingRequest::findOrFail($id);
        $path = (string) $request->query('path', '');

        abort_unless(in_array($path, $manufacturingRequest->images ?? [], true), 404);

        $file = ProductRequestFiles::tenantFilePath((string) $manufacturingRequest->tenant_id, $path, 'manufacturing-requests');
        abort_if($file === null, 404);

        return response()->file($file);
    }
}
