<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateLoginToken;
use App\Models\Tenant;
use App\Support\AffiliateSettings;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

/**
 * Lets a tenant (vendor store) act as an affiliate. Each tenant gets one row in the central
 * `affiliates` table, created at registration with the store owner's email and registration
 * password (so it can also log in to the affiliate panel). The partner balance is separate
 * from vendor order money.
 */
class TenantAffiliateService
{
    public function __construct(private AffiliateService $affiliates)
    {
    }

    public function forTenant(string|Tenant $tenant, ?string $plainPassword = null): Affiliate
    {
        $tenantId = $tenant instanceof Tenant ? (string) $tenant->getTenantKey() : $tenant;

        $existing = Affiliate::query()->where('tenant_id', $tenantId)->first();
        if ($existing) {
            return $existing;
        }

        $model = $tenant instanceof Tenant ? $tenant : Tenant::query()->find($tenantId);

        try {
            return Affiliate::query()->create([
                'type'             => 'tenant',
                'tenant_id'        => $tenantId,
                'name'             => (string) ($model?->shop_name ?: $model?->name ?: $tenantId),
                'email'            => $this->availableEmail((string) $model?->email, $tenantId),
                'password'         => $plainPassword ?: Str::random(48),
                'code'             => Affiliate::generateUniqueCode(),
                'commission_type'  => AffiliateSettings::defaultCommissionType(),
                'commission_value' => AffiliateSettings::defaultCommissionValue(),
                'status'           => 'active',
                'approved_at'      => now(),
            ]);
        } catch (QueryException $e) {
            // Lost a race on the unique tenant_id (or, very rarely, code): re-read the winner.
            $row = Affiliate::query()->where('tenant_id', $tenantId)->first();
            if ($row) {
                return $row;
            }
            throw $e;
        }
    }

    /**
     * The tenant's own email when no affiliate uses it yet; otherwise a unique variant
     * (`name+<tenant>@domain`, then `name+<tenant>-N@domain`) that still reaches the same inbox.
     */
    private function availableEmail(string $email, string $tenantId): string
    {
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "tenant-{$tenantId}@affiliates.invalid";
        }

        if (!Affiliate::query()->where('email', $email)->exists()) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $local = explode('+', $local, 2)[0];
        $tag   = Str::slug($tenantId) ?: 'store';

        for ($i = 0; ; $i++) {
            $candidate = "{$local}+{$tag}" . ($i ? "-{$i}" : '') . "@{$domain}";
            if (!Affiliate::query()->where('email', $candidate)->exists()) {
                return $candidate;
            }
        }
    }

    /** One-time (60s) URL on the central domain that signs the owner into this affiliate account. */
    public function loginUrl(Affiliate $a): string
    {
        $token = Str::random(64);

        AffiliateLoginToken::query()->where('expires_at', '<', now()->subDay())->delete();
        AffiliateLoginToken::query()->create([
            'affiliate_id' => $a->id,
            'token_hash'   => hash('sha256', $token),
            'expires_at'   => now()->addSeconds(60),
        ]);

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $port   = parse_url((string) config('app.url'), PHP_URL_PORT);

        return $scheme . '://' . config('tenancy.central_domains.0') . ($port ? ':' . $port : '') . '/affiliate/sso/' . $token;
    }

    public function referralUrl(Affiliate $a): string
    {
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $host   = config('tenancy.central_domains.0');
        $port   = parse_url((string) config('app.url'), PHP_URL_PORT);

        return $scheme . '://' . $host . ($port ? ':' . $port : '') . '/?ref=' . $a->code;
    }

    public function stats(Affiliate $a): array
    {
        $since = now()->subDays(30);

        $visits     = $a->referrals()->count();
        $registered = $a->referrals()->whereNotNull('converted_at')->count();

        return [
            'visits'            => $visits,
            'registered'        => $registered,
            'paying'            => $a->conversions()->whereIn('status', ['approved', 'paid'])->count(),
            'visits_30d'        => $a->referrals()->where('created_at', '>=', $since)->count(),
            'registered_30d'    => $a->referrals()->where('converted_at', '>=', $since)->count(),
            'balance'           => (float) $a->balance,
            'total_earned'      => (float) $a->total_earned,
            'total_paid'        => (float) $a->total_paid,
            'pending_requested' => (float) $a->payoutRequests()->where('status', 'pending')->sum('amount'),
            'requestable'       => $this->affiliates->requestableAmount($a),
            'min_payout'        => AffiliateSettings::minPayout(),
        ];
    }
}
