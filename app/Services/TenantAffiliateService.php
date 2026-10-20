<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Tenant;
use App\Support\AffiliateSettings;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

/**
 * Lets a tenant (vendor store) act as an affiliate. Tenant affiliates are non-login rows
 * in the central `affiliates` table; the partner balance is separate from vendor order money.
 */
class TenantAffiliateService
{
    public function __construct(private AffiliateService $affiliates)
    {
    }

    public function forTenant(string|Tenant $tenant): Affiliate
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
                'email'            => "tenant-{$tenantId}@affiliates.invalid",
                'password'         => Str::random(48),
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
