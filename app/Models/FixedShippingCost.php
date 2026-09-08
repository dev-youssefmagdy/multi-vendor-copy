<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class FixedShippingCost extends Model
{
    use HasFactory, CentralConnection;

    protected $fillable = [
        'country_id',
        'price_per_gram',
        'currency_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_gram' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    // ── Query scopes ──────────────────────────────────────────────────────────

    /** Scope: active records only. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Compute the shipping cost for a given product weight.
     */
    public function costForWeight(int $weightGrams): float
    {
        return round((float) $this->price_per_gram * $weightGrams, 2);
    }
}
