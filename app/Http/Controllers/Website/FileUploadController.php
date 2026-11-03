<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required'],
        ]);

        $path = \App\Support\ImageOptimizer::store($request->file('file'), 'uploads');

        return response()->json([
            'path' => $path,
            'file' => url(Storage::url($path)),
        ]);
    }
}
