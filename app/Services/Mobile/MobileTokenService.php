<?php

declare(strict_types=1);

namespace App\Services\Mobile;

use App\Models\Tenant\Customer;
use App\Models\Tenant\MobileAccessToken;
use Illuminate\Support\Str;

/**
 * Issues / verifies hashed, tenant-bound mobile tokens. Must run inside tenancy
 * (tokens live in the tenant database). Wire format: "{id}|{secret}".
 */
class MobileTokenService
{
    public const TTL_DAYS = 30;

    /** @return array{token:string,refresh_token:string,expires_at:string} */
    public function issue(Customer $customer, ?string $device = null): array
    {
        $secret = Str::random(64);
        $refresh = Str::random(64);

        $row = MobileAccessToken::create([
            'tenant_id' => (string) tenant('id'),
            'customer_id' => $customer->id,
            'device_name' => $device ? Str::limit($device, 120, '') : null,
            'token_hash' => hash('sha256', $secret),
            'refresh_hash' => hash('sha256', $refresh),
            'expires_at' => now()->addDays(self::TTL_DAYS),
        ]);

        return $this->payload($row, $secret, $refresh);
    }

    public function verify(?string $bearer): ?MobileAccessToken
    {
        [$id, $secret] = $this->split($bearer);
        if ($id === null) {
            return null;
        }

        $row = MobileAccessToken::query()->find($id);
        if (!$row || $row->revoked_at || ($row->expires_at && $row->expires_at->isPast())) {
            return null;
        }
        if ($row->tenant_id !== (string) tenant('id') || !hash_equals($row->token_hash, hash('sha256', $secret))) {
            return null;
        }

        return $row;
    }

    /** @return array{token:string,refresh_token:string,expires_at:string}|null rotates both secrets */
    public function refresh(?string $refreshToken): ?array
    {
        [$id, $secret] = $this->split($refreshToken);
        if ($id === null) {
            return null;
        }

        $row = MobileAccessToken::query()->find($id);
        if (!$row || $row->revoked_at || $row->tenant_id !== (string) tenant('id')
            || !hash_equals($row->refresh_hash, hash('sha256', $secret))) {
            return null;
        }

        $newSecret = Str::random(64);
        $newRefresh = Str::random(64);
        $row->update([
            'token_hash' => hash('sha256', $newSecret),
            'refresh_hash' => hash('sha256', $newRefresh),
            'expires_at' => now()->addDays(self::TTL_DAYS),
        ]);

        return $this->payload($row, $newSecret, $newRefresh);
    }

    public function revoke(MobileAccessToken $token): void
    {
        $token->update(['revoked_at' => now()]);
    }

    public function revokeAllFor(Customer $customer): void
    {
        MobileAccessToken::query()->where('customer_id', $customer->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    private function payload(MobileAccessToken $row, string $secret, string $refresh): array
    {
        return [
            'token' => $row->id.'|'.$secret,
            'refresh_token' => $row->id.'|'.$refresh,
            'expires_at' => $row->expires_at->toIso8601String(),
        ];
    }

    /** @return array{0:?int,1:string} */
    private function split(?string $value): array
    {
        if (!$value || !str_contains($value, '|')) {
            return [null, ''];
        }
        [$id, $secret] = explode('|', $value, 2);

        return ctype_digit($id) && $secret !== '' ? [(int) $id, $secret] : [null, ''];
    }
}
