<?php

namespace App\Models;

use App\Enums\Tenant\CouponType;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CentralCoupon extends Model
{
    protected $table = 'central_coupons';

    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'start_date',
        'end_date',
        'minimum_spend',
        'active',
        'affiliate_id',
        'affiliate_commission_value',
    ];

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'decimal:2',
            'minimum_spend' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'active' => 'boolean',
            'affiliate_commission_value' => 'decimal:2',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    /**
     * Countries this coupon is available in.
     * Empty pivot (no rows) = available everywhere (all countries).
     */
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'central_coupon_country');
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Whether this coupon is available for a given central country ID.
     * Null countryId (unknown visitor) = allow (show everything).
     */
    public function availableInCountry(?int $countryId): bool
    {
        if (!$countryId) {
            return true; // unknown country — show all
        }

        // Load if not already eager-loaded
        $assigned = $this->relationLoaded('countries')
            ? $this->countries
            : $this->countries()->get();

        // Empty pivot = available everywhere
        if ($assigned->isEmpty()) {
            return true;
        }

        return $assigned->contains('id', $countryId);
    }

    public function hasAffiliate(): bool
    {
        return $this->affiliate_id !== null;
    }

    /**
     * Calculate the commission amount for a given sale amount.
     * Uses the coupon-specific override rate (always a percentage) if set,
     * otherwise falls back to the affiliate's own commission_type + commission_value.
     */
    public function calculateAffiliateCommission(float $saleAmount): float
    {
        if (!$this->affiliate_id) {
            return 0.0;
        }

        if ($this->affiliate_commission_value !== null) {
            return round($saleAmount * (float) $this->affiliate_commission_value / 100, 2);
        }

        $this->loadMissing('affiliate');

        return $this->affiliate?->calculateCommission($saleAmount) ?? 0.0;
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive(Builder $query, ?CarbonInterface $moment = null): Builder
    {
        $moment ??= Carbon::now();

        return $query
            ->where('active', true)
            ->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $moment))
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $moment));
    }
}
