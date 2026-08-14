<?php

namespace App\Services\Tenant;

use App\Models\CustomTemplate;
use App\Models\Tenant as TenantModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Handles secure upload, validation, and extraction of tenant custom
 * storefront templates. Extracted files live under the private "local"
 * disk (storage/app), never inside the public web root, so uploaded content
 * can never be executed as PHP — it is only ever served through the
 * controlled CustomTemplateController which forces safe content-types.
 */
class CustomTemplateService
{
    /** Disallowed file extensions inside the uploaded zip (case-insensitive). */
    private const DANGEROUS_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'pht', 'phar',
        'exe', 'sh', 'bash', 'bat', 'cmd', 'cgi', 'pl', 'py', 'rb',
        'jsp', 'asp', 'aspx', 'htaccess', 'config',
    ];

    /** Allowed top-level folders besides index.html. */
    private const ALLOWED_FOLDERS = ['assets', 'css', 'js', 'images'];

    private const BASE_DIR = 'tenant-templates';

    public function baseStoragePath(string $tenantId): string
    {
        return self::BASE_DIR . '/' . $tenantId;
    }

    public function versionStoragePath(string $tenantId, string $version): string
    {
        return $this->baseStoragePath($tenantId) . '/' . $version;
    }

    /**
     * Validate the zip's entry list without extracting anything: checks for
     * a root-level index.html, rejects path traversal, and rejects any
     * dangerous file extensions. Throws RuntimeException with a
     * user-facing message on the first violation found.
     */
    public function validateZip(string $absoluteZipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($absoluteZipPath) !== true) {
            throw new RuntimeException('The uploaded file is not a valid ZIP archive.');
        }

        try {
            $hasIndex = false;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($name === false) {
                    continue;
                }

                $normalized = str_replace('\\', '/', $name);

                // Reject absolute paths and path traversal (zip-slip).
                if (Str::startsWith($normalized, '/') || str_contains($normalized, '../') || str_contains($normalized, '..\\')) {
                    throw new RuntimeException('The archive contains an invalid or unsafe path: ' . $name);
                }

                // Skip directory entries.
                if (Str::endsWith($normalized, '/')) {
                    continue;
                }

                $segments = explode('/', ltrim($normalized, '/'));
                $topLevel = $segments[0] ?? '';

                if (count($segments) === 1) {
                    if (strtolower($topLevel) === 'index.html') {
                        $hasIndex = true;
                    }
                } elseif (!in_array(strtolower($topLevel), self::ALLOWED_FOLDERS, true)) {
                    throw new RuntimeException(
                        "Unexpected top-level folder \"{$topLevel}\". Only assets/, css/, js/, and images/ are allowed besides index.html."
                    );
                }

                $extension = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));
                if ($extension !== '' && in_array($extension, self::DANGEROUS_EXTENSIONS, true)) {
                    throw new RuntimeException("The archive contains a disallowed file type: {$name}");
                }
            }

            if (!$hasIndex) {
                throw new RuntimeException('The ZIP archive must contain an index.html file at its root.');
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * Validate then extract the uploaded zip into a private, non-executable
     * storage directory scoped to the tenant + version, and register a
     * pending CustomTemplate record for admin review.
     */
    public function uploadAndExtract(TenantModel|string $tenant, UploadedFile $file): CustomTemplate
    {
        $tenantId = is_string($tenant) ? $tenant : $tenant->getTenantKey();

        $absolutePath = $file->getRealPath();
        if (!$absolutePath) {
            throw new RuntimeException('The uploaded file could not be read.');
        }

        $this->validateZip($absolutePath);

        $version = now()->format('YmdHis') . '-' . Str::random(6);
        $relativePath = $this->versionStoragePath($tenantId, $version);
        $absoluteExtractPath = storage_path('app/' . $relativePath);

        if (!is_dir($absoluteExtractPath)) {
            mkdir($absoluteExtractPath, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            throw new RuntimeException('The uploaded file could not be opened for extraction.');
        }

        try {
            $zip->extractTo($absoluteExtractPath);
        } finally {
            $zip->close();
        }

        return tenancy()->central(function () use ($tenantId, $version, $relativePath, $file) {
            return CustomTemplate::query()->create([
                'tenant_id' => $tenantId,
                'version' => $version,
                'storage_path' => $relativePath,
                'original_filename' => $file->getClientOriginalName(),
                'status' => CustomTemplate::STATUS_PENDING,
                'is_active' => false,
                'uploaded_at' => now(),
            ]);
        });
    }

    /** Resolve the absolute filesystem path to a stored template file, guarding against traversal. */
    public function resolveFile(string $tenantId, string $version, string $relativeFile): ?string
    {
        $relativeFile = str_replace('\\', '/', $relativeFile);

        if ($relativeFile === '' || str_contains($relativeFile, '..')) {
            return null;
        }

        $base = realpath(storage_path('app/' . $this->versionStoragePath($tenantId, $version)));
        if (!$base) {
            return null;
        }

        $full = realpath($base . '/' . $relativeFile);
        if (!$full || !Str::startsWith($full, $base)) {
            return null;
        }

        return is_file($full) ? $full : null;
    }

    public function activeTemplate(string $tenantId): ?CustomTemplate
    {
        return tenancy()->central(fn() => CustomTemplate::query()
            ->where('tenant_id', $tenantId)
            ->where('status', CustomTemplate::STATUS_APPROVED)
            ->where('is_active', true)
            ->latest('id')
            ->first());
    }

    public function latestTemplate(string $tenantId): ?CustomTemplate
    {
        return tenancy()->central(fn() => CustomTemplate::query()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->first());
    }

    public function activate(string $tenantId, int $templateId): void
    {
        tenancy()->central(function () use ($tenantId, $templateId) {
            $template = CustomTemplate::query()
                ->where('tenant_id', $tenantId)
                ->where('status', CustomTemplate::STATUS_APPROVED)
                ->findOrFail($templateId);

            CustomTemplate::query()->where('tenant_id', $tenantId)->update(['is_active' => false]);
            $template->update(['is_active' => true]);
        });
    }

    public function deactivate(string $tenantId): void
    {
        tenancy()->central(function () use ($tenantId) {
            CustomTemplate::query()->where('tenant_id', $tenantId)->update(['is_active' => false]);
        });
    }

    /**
     * Safely inject an HTML snippet just before </head>. Falls back to
     * prepending to the document if no </head> tag is present.
     */
    public function injectIntoHead(string $html, string $snippet): string
    {
        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $snippet . "\n</head>", $html, 1);
        }

        return $snippet . $html;
    }

    /**
     * Rewrite relative references to the template's allowed asset folders
     * (assets/, css/, js/, images/) so they resolve through the controlled
     * asset-serving route instead of the app's own root-relative paths.
     */
    public function rewriteAssetPaths(string $html, string $assetBaseUrl): string
    {
        $assetBaseUrl = rtrim($assetBaseUrl, '/');

        return preg_replace_callback(
            '/(href|src)=("|\')(assets|css|js|images)\/([^"\']*)\2/i',
            function (array $m) use ($assetBaseUrl) {
                return $m[1] . '=' . $m[2] . $assetBaseUrl . '/' . $m[3] . '/' . $m[4] . $m[2];
            },
            $html
        );
    }
}
