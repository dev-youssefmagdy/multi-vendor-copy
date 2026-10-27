<?php

namespace App\Models;

use App\Enums\Tenant\CouponType;
use App\Models\Concerns\HasCountries;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CentralCoupon extends Model
{
    use HasCountries;

    protected string $countryPivotTable = 'central_coupon_country';

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
