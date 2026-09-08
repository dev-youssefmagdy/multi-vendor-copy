<?php

namespace App\Models\Tenant;

use App\Enums\Tenant\CouponType;
use App\Models\Tenant\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;
    use HasTranslations;

    protected array $translated = ['name'];

    protected $fillable = [
        'central_coupon_id',
        'code',
        'type',
        'value',
        'start_date',
        'end_date',
        'minimum_spend',
        'country_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'decimal:2',
            'minimum_spend' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    /**
     * Whether this tenant coupon is usable in the given country.
     * NULL country_id (Default) = available everywhere.
     */
    public function availableInCountry(?int $countryId): bool
    {
        if ($this->country_id === null) {
            return true;
        }

        return $countryId !== null && (int) $countryId === (int) $this->country_id;
    }
}
