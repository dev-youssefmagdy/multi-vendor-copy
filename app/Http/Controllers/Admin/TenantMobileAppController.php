<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantMobileApp;
use App\Services\Mobile\MobileAppLinks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Central admin > Tenants > Edit tenant > "Mobile App" tab: the tenant's own white-label app ids
 * (assetlinks / apple-app-site-association on its domains, /app landing, payment return scheme).
 * Values come from application/tenants/<id>/tenant.json + the store consoles (PUBLISH_TO_STORES.md §5-§7).
 */
class TenantMobileAppController extends Controller
{
    public function update(Request $request, Tenant $tenant, MobileAppLinks $links): RedirectResponse
    {
        $request->merge([
            'android_package' => $this->clean($request->input('android_package')),
            'ios_team_id' => ($v = $this->clean($request->input('ios_team_id'))) !== null ? strtoupper($v) : null,
            'ios_bundle_id' => $this->clean($request->input('ios_bundle_id')),
            'url_scheme' => ($v = $this->clean($request->input('url_scheme'))) !== null ? strtolower($v) : null,
            'play_store_url' => $this->clean($request->input('play_store_url')),
            'app_store_url' => $this->clean($request->input('app_store_url')),
        ]);

        $fingerprints = TenantMobileApp::splitFingerprints((string) $request->input('android_sha256'));
        $normalized = [];

        $validated = $request->validate([
            '_tab' => ['nullable', 'string'],
            'android_package' => [
                'nullable', 'string', 'max:150', 'regex:'.TenantMobileApp::ANDROID_PACKAGE_REGEX,
                'required_with:android_sha256',
                Rule::notIn(array_filter([(string) config('mobile.android_package')])),
            ],
            'android_sha256' => [
                'nullable', 'string', 'max:2000', 'required_with:android_package',
                function (string $attribute, mixed $value, \Closure $fail) use ($fingerprints, &$normalized) {
                    foreach ($fingerprints as $raw) {
                        $fp = TenantMobileApp::normalizeFingerprint($raw);
                        if ($fp === null) {
                            $fail('Each SHA-256 fingerprint must be 64 hex characters (colons optional): "'.mb_strimwidth($raw, 0, 40, '…').'" is not.');

                            return;
                        }
                        $normalized[$fp] = $fp;
                    }
                    if (count($normalized) > 10) {
                        $fail('At most 10 SHA-256 fingerprints.');
                    }
                },
            ],
            'ios_team_id' => ['nullable', 'string', 'regex:'.TenantMobileApp::IOS_TEAM_ID_REGEX, 'required_with:ios_bundle_id'],
            'ios_bundle_id' => [
                'nullable', 'string', 'max:155', 'regex:'.TenantMobileApp::IOS_BUNDLE_ID_REGEX, 'required_with:ios_team_id',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    $platform = (string) config('mobile.ios_app_id');
                    if ($platform !== '' && $request->input('ios_team_id').'.'.$value === $platform) {
                        $fail('This is the platform app\'s iOS app id; a store app needs its own bundle id.');
                    }
                },
            ],
            'url_scheme' => [
                'nullable', 'string', 'min:2', 'max:41', 'regex:'.TenantMobileApp::URL_SCHEME_REGEX,
                Rule::notIn(array_merge(TenantMobileApp::RESERVED_SCHEMES, [$links->platformScheme()])),
                Rule::unique(TenantMobileApp::class, 'url_scheme')->ignore($tenant->getKey(), 'tenant_id'),
            ],
            'play_store_url' => ['nullable', 'string', 'max:500', 'url:https'],
            'app_store_url' => ['nullable', 'string', 'max:500', 'url:https'],
        ], [
            'android_package.regex' => 'Use the Android application id from tenant.json (e.g. com.brand.shop).',
            'android_package.not_in' => 'This is the platform app\'s package; a store app needs its own application id.',
            'ios_team_id.regex' => 'The Apple Team ID is 10 uppercase letters/digits.',
            'ios_bundle_id.regex' => 'Use the iOS bundle id from tenant.json (e.g. com.brand.shop).',
            'url_scheme.regex' => 'Lowercase letters first, then a-z, 0-9, "+", "." or "-".',
            'url_scheme.not_in' => 'This scheme is reserved or used by the platform app; use one unique to this store.',
            'url_scheme.unique' => 'Another store\'s app already uses this scheme (apps sharing a scheme steal each other\'s payment returns).',
        ]);

        $values = [
            'android_package' => $validated['android_package'] ?? null,
            'android_sha256' => $normalized ? array_values($normalized) : null,
            'ios_team_id' => $validated['ios_team_id'] ?? null,
            'ios_bundle_id' => $validated['ios_bundle_id'] ?? null,
            'url_scheme' => $validated['url_scheme'] ?? null,
            'play_store_url' => $validated['play_store_url'] ?? null,
            'app_store_url' => $validated['app_store_url'] ?? null,
        ];

        if (array_filter($values) === []) {
            TenantMobileApp::query()->where('tenant_id', $tenant->getKey())->delete();
        } else {
            TenantMobileApp::query()->updateOrCreate(['tenant_id' => $tenant->getKey()], $values);
        }

        return redirect()
            ->route('admin.plans.users.edit', $tenant)
            ->with('status', 'Mobile app settings saved.')
            ->withInput(['_tab' => 'mobile']);
    }

    private function clean(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
