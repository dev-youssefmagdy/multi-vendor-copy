<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * A tenant's own (white-label) mobile app identity. Central table, edited only by the platform admin.
 * Values mirror application/tenants/<id>/tenant.json (android.applicationId, ios.teamId + ios.bundleId, urlScheme).
 *
 * @property string $tenant_id
 * @property string|null $android_package
 * @property array<int, string>|null $android_sha256
 * @property string|null $ios_team_id
 * @property string|null $ios_bundle_id
 * @property string|null $url_scheme
 * @property string|null $play_store_url
 * @property string|null $app_store_url
 */
class TenantMobileApp extends Model
{
    use CentralConnection;

    /** Same rules as application/tool/tenant_builder (validator.dart). */
    public const ANDROID_PACKAGE_REGEX = '/^[a-zA-Z][a-zA-Z0-9_]*(\.[a-zA-Z][a-zA-Z0-9_]*)+$/';
    public const IOS_BUNDLE_ID_REGEX = '/^[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)+$/';
    public const IOS_TEAM_ID_REGEX = '/^[A-Z0-9]{10}$/';
    public const URL_SCHEME_REGEX = '/^[a-z][a-z0-9+.-]*$/';

    /** Schemes an app must never claim (browsers / OS handlers). */
    public const RESERVED_SCHEMES = [
        'http', 'https', 'file', 'content', 'intent', 'javascript', 'data', 'about', 'market', 'mailto', 'tel', 'sms',
        'payment', 'app', 'android', 'ftp', 'itms-apps', 'itms-services', 'blob', 'ws', 'wss',
    ];

    protected $fillable = [
        'tenant_id',
        'android_package',
        'android_sha256',
        'ios_team_id',
        'ios_bundle_id',
        'url_scheme',
        'play_store_url',
        'app_store_url',
    ];

    protected $casts = [
        'android_sha256' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** "<TeamID>.<BundleID>" or null when either part is missing. */
    public function iosAppId(): ?string
    {
        return $this->ios_team_id && $this->ios_bundle_id ? $this->ios_team_id.'.'.$this->ios_bundle_id : null;
    }

    /** @return array<int, string> */
    public function fingerprints(): array
    {
        return array_values(array_filter((array) ($this->android_sha256 ?? []), 'is_string'));
    }

    /**
     * Normalises one SHA-256 certificate fingerprint to the form Google's assetlinks expects
     * ("14:6D:E9:..." - 32 uppercase hex pairs). Accepts colons, spaces or dashes as separators, or none.
     * Returns null when the value is not exactly 64 hex digits.
     */
    public static function normalizeFingerprint(string $value): ?string
    {
        $hex = strtoupper((string) preg_replace('/[\s:\-]/', '', $value));
        if (!preg_match('/^[0-9A-F]{64}$/', $hex)) {
            return null;
        }

        return implode(':', str_split($hex, 2));
    }

    /**
     * Splits a textarea / comma separated list into raw fingerprint strings.
     *
     * @return array<int, string>
     */
    public static function splitFingerprints(?string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', (string) $value) ?: [])));
    }
}
