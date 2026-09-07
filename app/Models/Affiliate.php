<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Affiliate extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'code',
        'commission_type', 'commission_value',
        'balance', 'total_earned', 'total_paid',
        'status', 'paypal_email',
        'bank_name', 'bank_account', 'bank_iban',
        'notes', 'approved_at',
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

    public function payouts(): HasMany
    {
        return $this->hasMany(AffiliatePayout::class);
    }

    // ── Helpers ────────────────────────────────────────────────────
    public function referralUrl(): string
    {
        return route('website.home') . '?ref=' . $this->code;
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
        return match ($this->commission_type) {
            'percentage' => round($saleAmount * (float) $this->commission_value / 100, 2),
            'fixed'      => (float) $this->commission_value,
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
