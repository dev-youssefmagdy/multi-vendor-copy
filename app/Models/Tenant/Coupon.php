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
        'allowed_country_ids',
    ];

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'decimal:2',
            'minimum_spend' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'allowed_country_ids' => 'array',
        ];
    }

    /**
     * Whether this tenant coupon is usable in the given country.
     * NULL / empty allowed_country_ids = available everywhere.
     */
    public function availableInCountry(?int $countryId): bool
    {
        if (!$countryId) {
            return true;
        }

        $ids = $this->allowed_country_ids;

        if (empty($ids)) {
            return true;
        }

        return in_array($countryId, (array) $ids, strict: false);
    }
}
