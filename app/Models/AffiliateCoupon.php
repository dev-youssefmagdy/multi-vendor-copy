<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliateCoupon extends Model
{
    protected $table = 'affiliate_coupons';

    protected $fillable = [
        'affiliate_id',
        'code',
        'discount_type',
        'discount_value',
        'commission_value',
        'start_date',
        'end_date',
        'minimum_spend',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value'   => 'decimal:2',
            'commission_value' => 'decimal:2',
            'minimum_spend'    => 'decimal:2',
            'start_date'       => 'datetime',
            'end_date'         => 'datetime',
            'active'           => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(AffiliateConversion::class, 'affiliate_coupon_id');
    }

    // ── Scopes ────────────────────────────────────────────────────
    public function scopeActive(Builder $query, ?Carbon $moment = null): Builder
    {
        $moment ??= Carbon::now();

        return $query
            ->where('active', true)
            ->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $moment))
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $moment));
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Calculate the discount amount the customer receives.
     */
    public function calculateDiscount(float $originalPrice): float
    {
        return match ($this->discount_type) {
            'percentage' => round($originalPrice * (float) $this->discount_value / 100, 2),
            'fixed'      => min((float) $this->discount_value, $originalPrice),
            default      => 0.0,
        };
    }

    /**
     * Calculate the commission the affiliate earns on a given sale amount.
     * Uses coupon-level override if set, otherwise falls back to affiliate's default.
     */
    public function calculateCommission(float $saleAmount): float
    {
        $this->loadMissing('affiliate');

        if ($this->commission_value !== null) {
            // Coupon-level override — always a percentage
            return round($saleAmount * (float) $this->commission_value / 100, 2);
        }

        return $this->affiliate?->calculateCommission($saleAmount) ?? 0.0;
    }

    /**
     * Human-readable discount label (e.g. "20% off" or "$10 off").
     */
    public function discountLabel(): string
    {
        return $this->discount_type === 'percentage'
            ? number_format((float) $this->discount_value, 0) . '% off'
            : '$' . number_format((float) $this->discount_value, 2) . ' off';
    }
}
