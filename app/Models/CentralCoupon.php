<?php

namespace App\Models;

use App\Enums\Tenant\CouponType;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
