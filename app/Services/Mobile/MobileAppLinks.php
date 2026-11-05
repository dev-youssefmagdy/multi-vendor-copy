<?php

declare(strict_types=1);

namespace App\Services\Mobile;

use App\Models\Tenant;
use App\Models\TenantMobileApp;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * Which mobile apps belong to a domain, and which custom URL scheme a redirect may use.
 *
 * - Platform (generic) app: config/mobile.php (MOBILE_* env).
 * - Tenant white-label app: central `tenant_mobile_apps` row, set in the central admin.
 *
 * Scheme rule (no open redirect): a redirect only ever uses the platform scheme or the scheme configured for
 * the current tenant. A client-supplied scheme (X-App-Scheme header / app_scheme query) is accepted only when it
 * equals one of those two; anything else falls back to the platform scheme.
 */
class MobileAppLinks
{
    public const SCHEME_HEADER = 'X-App-Scheme';
    public const SCHEME_QUERY = 'app_scheme';

    public function platformScheme(): string
    {
        $scheme = strtolower(trim((string) config('mobile.deep_link_scheme', 'storeapp')));

        return preg_match(TenantMobileApp::URL_SCHEME_REGEX, $scheme) ? $scheme : 'storeapp';
    }

    public function appFor(?Tenant $tenant): ?TenantMobileApp
    {
        if (!$tenant) {
            return null;
        }

        try {
            return TenantMobileApp::query()->where('tenant_id', $tenant->getKey())->first();
        } catch (QueryException $e) {
            // Table not migrated yet: behave as "no white-label app" so payment returns / landing keep working.
            report($e);

            return null;
        }
    }

    /** Tenant owning a request host (subdomain or custom domain); null on central / unknown hosts. */
    public function tenantForHost(string $host): ?Tenant
    {
        $host = strtolower(rtrim(trim($host), '.'));
        if ($host === '' || in_array($host, (array) config('tenancy.central_domains', []), true)) {
            return null;
        }
        $tenantId = Domain::query()->where('domain', $host)->value('tenant_id');

        return $tenantId ? Tenant::find($tenantId) : null;
    }

    /** The tenant's own scheme, when it has a white-label app with one. */
    public function tenantScheme(?Tenant $tenant): ?string
    {
        $scheme = $this->appFor($tenant)?->url_scheme;

        return $scheme !== null && $scheme !== '' ? $scheme : null;
    }

    /** Returns $candidate when it is the platform scheme or this tenant's scheme, otherwise null. */
    public function acceptScheme(mixed $candidate, ?Tenant $tenant): ?string
    {
        if (!is_string($candidate)) {
            return null;
        }
        $candidate = strtolower(trim($candidate));
        if ($candidate === '' || !preg_match(TenantMobileApp::URL_SCHEME_REGEX, $candidate)) {
            return null;
        }
        if ($candidate === $this->platformScheme()) {
            return $candidate;
        }

        return $candidate === $this->tenantScheme($tenant) ? $candidate : null;
    }

    /** Scheme the requesting app announced (X-App-Scheme), validated; null when absent or not allowed. */
    public function requestScheme(Request $request, ?Tenant $tenant): ?string
    {
        return $this->acceptScheme($request->header(self::SCHEME_HEADER), $tenant);
    }

    /** @return array<int, array<string, mixed>> assetlinks.json statements */
    public function assetLinks(?Tenant $tenant): array
    {
        $apps = [];
        if ($app = $this->appFor($tenant)) {
            if ($app->android_package && $app->fingerprints()) {
                $apps[$app->android_package] = $app->fingerprints();
            }
        }
        $platformPackage = (string) config('mobile.android_package');
        $platformFingerprints = array_values(array_filter(array_map('trim', explode(',', (string) config('mobile.android_sha256')))));
        if ($platformPackage !== '' && $platformFingerprints) {
            $apps[$platformPackage] = array_values(array_unique(array_merge($apps[$platformPackage] ?? [], $platformFingerprints)));
        }

        $statements = [];
        foreach ($apps as $package => $fingerprints) {
            $statements[] = [
                'relation' => ['delegate_permission/common.handle_all_urls'],
                'target' => ['namespace' => 'android_app', 'package_name' => $package, 'sha256_cert_fingerprints' => $fingerprints],
            ];
        }

        return $statements;
    }

    /** @return array<int, string> "<TeamID>.<BundleID>" ids for apple-app-site-association */
    public function iosAppIds(?Tenant $tenant): array
    {
        $ids = [];
        if ($id = $this->appFor($tenant)?->iosAppId()) {
            $ids[] = $id;
        }
        if ($platform = trim((string) config('mobile.ios_app_id'))) {
            $ids[] = $platform;
        }

        return array_values(array_unique($ids));
    }
}
