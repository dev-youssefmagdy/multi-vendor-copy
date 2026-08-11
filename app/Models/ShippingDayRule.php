<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Central model — stores the default shipping duration (min/max days) per
 * country, with a global fallback for rows where country_code is NULL.
 *
 * @property int         $id
 * @property string|null $country_code  ISO 3166-1 alpha-2 (null = global default)
 * @property int         $min_days
 * @property int         $max_days
 */
class ShippingDayRule extends Model
{
    use CentralConnection;
    protected $fillable = ['country_code', 'min_days', 'max_days'];

    protected $casts = [
        'min_days' => 'integer',
        'max_days' => 'integer',
    ];

    /**
     * Resolve the shipping-day rule for a given ISO-2 country code.
     * Falls back to the global default (country_code IS NULL) when no
     * country-specific row exists.  Returns null only when the table is empty.
     */
    public static function forCountry(?string $countryCode): ?self
    {
        if ($countryCode) {
            $rule = static::query()
                ->where('country_code', strtoupper($countryCode))
                ->first();

            if ($rule) {
                return $rule;
            }
        }

        return static::query()->whereNull('country_code')->first();
    }
}
