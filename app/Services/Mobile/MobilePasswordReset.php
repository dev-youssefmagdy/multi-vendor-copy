<?php

declare(strict_types=1);

namespace App\Services\Mobile;

use App\Models\Tenant\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Minimal customer password reset on the tenant `password_reset_tokens` table (hashed token, 60 min). */
class MobilePasswordReset
{
    public const TTL_MINUTES = 60;

    /** @return string|null plain token (to e-mail), null when no active customer has that e-mail */
    public function issue(string $email): ?string
    {
        $customer = Customer::query()->where('email', $email)->where('active', true)->first();
        if (!$customer) {
            return null;
        }

        $plain = Str::random(48);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $customer->email],
            ['token' => hash('sha256', $plain), 'created_at' => now()],
        );

        return $plain;
    }

    public function reset(string $email, string $token, string $password, MobileTokenService $tokens): bool
    {
        $row = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (!$row || !hash_equals((string) $row->token, hash('sha256', $token))
            || now()->diffInMinutes(\Illuminate\Support\Carbon::parse($row->created_at), true) > self::TTL_MINUTES) {
            return false;
        }

        $customer = Customer::query()->where('email', $email)->where('active', true)->first();
        if (!$customer) {
            return false;
        }

        $customer->forceFill(['password' => Hash::make($password)])->save();
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        $tokens->revokeAllFor($customer);

        return true;
    }
}
