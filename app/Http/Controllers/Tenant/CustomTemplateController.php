<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Models\CustomTemplate;
use App\Services\Tenant\CustomTemplateService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves extracted custom-template files from the private, non-web-root
 * storage directory. Never executes uploaded content — files are streamed
 * back with an allowlisted, forced Content-Type so a mislabeled or renamed
 * PHP file can never run as PHP even if it slipped past upload validation.
 */
class CustomTemplateController
{
    private const MIME_MAP = [
        'html' => 'text/html; charset=UTF-8',
        'htm' => 'text/html; charset=UTF-8',
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'mjs' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
        'txt' => 'text/plain; charset=UTF-8',
        'map' => 'application/json; charset=UTF-8',
    ];

    /** Preview an uploaded (pending/approved/rejected) template's own file, owner only, tenant panel context. */
    public function preview(Request $request, CustomTemplateService $service, string $version, string $file = 'index.html'): Response
    {
        $tenantId = (string) tenant()->getTenantKey();

        $template = tenancy()->central(fn() => CustomTemplate::query()
            ->where('tenant_id', $tenantId)
            ->where('version', $version)
            ->first());

        if (!$template) {
            abort(404);
        }

        return $this->serveFile($service, $tenantId, $version, $file, injectPixels: false);
    }

    /** Serve the tenant's live, approved & activated custom template on the storefront. */
    public function live(Request $request, CustomTemplateService $service, string $file = 'index.html'): Response
    {
        $tenantId = (string) tenant()->getTenantKey();
        $template = $service->activeTemplate($tenantId);

        if (!$template) {
            abort(404);
        }

        return $this->serveFile($service, $tenantId, $template->version, $file, injectPixels: true);
    }

    private function serveFile(CustomTemplateService $service, string $tenantId, string $version, string $file, bool $injectPixels): Response
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (!array_key_exists($extension, self::MIME_MAP)) {
            abort(404);
        }

        $absolutePath = $service->resolveFile($tenantId, $version, $file);
        if (!$absolutePath) {
            abort(404);
        }

        $contents = file_get_contents($absolutePath);

        if (in_array($extension, ['html', 'htm'], true)) {
            // Build the base URL relative to the currently matched route rather than
            // via named routes, since this controller is shared by both the
            // subdomain and path-based storefront route files (different route names).
            $currentUrl = request()->url();
            $basePath = preg_replace('#/custom-template(-assets)?(/preview/[^/]+)?$#', '', rtrim($currentUrl, '/'));
            $assetBaseUrl = $injectPixels
                ? $basePath . '/custom-template-assets'
                : $basePath . '/custom-template/preview/' . $version;
            $contents = $service->rewriteAssetPaths($contents, $assetBaseUrl);
        }

        if ($injectPixels && in_array($extension, ['html', 'htm'], true)) {
            $trackingSnippet = view('storefront.partials.tracking-scripts')->render();
            $contents = $service->injectIntoHead($contents, $trackingSnippet);
        }

        $headers = [
            'Content-Type' => self::MIME_MAP[$extension],
            'X-Content-Type-Options' => 'nosniff',
        ];

        if (in_array($extension, ['html', 'htm'], true)) {
            // Never let the extracted document be framed outside our own sandboxed iframe.
            $headers['Content-Security-Policy'] = "frame-ancestors 'self'";
        }

        return response($contents, 200, $headers);
    }
}
