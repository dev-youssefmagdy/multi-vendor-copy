<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Affiliate extends Authenticatable
{
    use Notifiable, CentralConnection;

    protected $fillable = [
        'name', 'email', 'password', 'code',
        'commission_type', 'commission_value',
        'balance', 'total_earned', 'total_paid',
        'status', 'paypal_email',
        'bank_name', 'bank_account', 'bank_iban',
        'notes', 'approved_at', 'type', 'tenant_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'         => 'hashed',
            'commission_value' => 'decimal:2',
            'balance'          => 'decimal:2',
            'total_earned'     => 'decimal:2',
            'total_paid'       => 'decimal:2',
            'approved_at'      => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────
    public function referrals(): HasMany
    {
        return $this->hasMany(AffiliateReferral::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(AffiliateConversion::class);
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(AffiliatePayoutRequest::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(AffiliatePayout::class);
    }

    // ── Helpers ────────────────────────────────────────────────────
    public function referralUrl(): string
    {
        return route('website.home') . '?ref=' . $this->code;
    }

    public function isTenant(): bool
    {
        return $this->type === 'tenant';
    }

    public function scopeExternal($query)
    {
        return $query->where('type', 'external');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Calculate the commission amount for a given sale amount.
     */
    public function calculateCommission(float $saleAmount): float
    {
        return static::commissionFor($this->commission_type, (float) $this->commission_value, $saleAmount);
    }

    /**
     * Commission for a type/value pair. A fixed commission never exceeds the sale amount.
     */
    public static function commissionFor(?string $type, float $value, float $saleAmount): float
    {
        return match ($type) {
            'percentage' => round($saleAmount * $value / 100, 2),
            'fixed'      => round(min($value, max(0.0, $saleAmount)), 2),
            default      => 0.0,
        };
    }

    // ── Static ─────────────────────────────────────────────────────
    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }
}
