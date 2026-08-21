<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class BladeThemeStarterKitController extends Controller
{
    /** Zips resources/blade-theme-starter-kit/ on the fly and streams it. */
    public function download(): StreamedResponse
    {
        $sourceDir = resource_path('blade-theme-starter-kit');
        $tmpZip    = storage_path('app/tmp/blade-theme-starter-kit-' . Str::random(8) . '.zip');

        if (!is_dir(dirname($tmpZip))) {
            mkdir(dirname($tmpZip), 0755, true);
        }

        $zip = new ZipArchive();
        $zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }
            $filePath     = $file->getRealPath();
            $relativePath = substr($filePath, strlen($sourceDir) + 1);
            $zip->addFile($filePath, $relativePath);
        }

        $zip->close();

        return response()->streamDownload(function () use ($tmpZip) {
            echo file_get_contents($tmpZip);
            @unlink($tmpZip);
        }, 'blade-theme-starter-kit.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }
}
