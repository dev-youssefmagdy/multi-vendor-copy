<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ProductRequest;

/**
 * PANELS-UPDATES-PRODUCT-REQUESTS: resolves the URL of a product-request attachment.
 *
 * Tenant uploads are written while tenancy is initialised, i.e. to the tenant-suffixed public disk
 * (storage/tenant{id}/app/public), so `asset('storage/...')` 404s for them. Admin replies are written from
 * the central context (storage/app/public) and are reachable through the public symlink.
 *  - the tenant panel serves its own files through stancl's /tenancy/assets route (tenant_asset());
 *  - the admin panel streams tenant files through admin.product-requests.file (permission checked).
 */
final class ProductRequestFiles
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];

    public static function isImage(string $path): bool
    {
        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::IMAGE_EXTENSIONS, true);
    }

    /**
     * @param  string[]  $paths
     * @param  bool  $sentByTenant  true for files uploaded by the tenant, false for admin replies
     * @return array<int, array{url: string, name: string, image: bool}>
     */
    public static function forAdmin(ProductRequest $request, array $paths, bool $sentByTenant): array
    {
        return array_map(fn (string $path) => [
            'url' => $sentByTenant
                ? route('admin.product-requests.file', ['requestId' => $request->id, 'path' => $path])
                : asset('storage/'.$path),
            'name' => basename($path),
            'image' => self::isImage($path),
        ], array_values(array_filter($paths, 'is_string')));
    }

    /**
     * @param  string[]  $paths
     * @return array<int, array{url: string, name: string, image: bool}>
     */
    public static function forTenant(array $paths, bool $sentByTenant): array
    {
        return array_map(fn (string $path) => [
            'url' => $sentByTenant ? tenant_asset($path) : asset('storage/'.$path),
            'name' => basename($path),
            'image' => self::isImage($path),
        ], array_values(array_filter($paths, 'is_string')));
    }

    /** Absolute path of a tenant-uploaded attachment inside that tenant's storage, or null when unsafe/missing. */
    public static function tenantFilePath(string $tenantId, string $path): ?string
    {
        if ($path === '' || str_contains($path, '..') || ! str_starts_with($path, "product-requests/{$tenantId}")) {
            return null;
        }

        $base = realpath(storage_path('tenant'.$tenantId.'/app/public'));
        $file = $base ? realpath($base.DIRECTORY_SEPARATOR.$path) : false;

        return $file && str_starts_with($file, $base.DIRECTORY_SEPARATOR) && is_file($file) ? $file : null;
    }
}
