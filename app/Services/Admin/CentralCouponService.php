<?php

namespace App\Services\Admin;

use App\Models\CentralCoupon;
use Illuminate\Support\Facades\DB;

class CentralCouponService
{
    public function save(array $attributes, ?CentralCoupon $coupon = null): CentralCoupon
    {
        return DB::transaction(function () use ($attributes, $coupon) {
            $coupon ??= new CentralCoupon();

            $coupon->fill([
                'code' => strtoupper(trim($attributes['code'])),
                'name' => filled($attributes['name'] ?? '') ? trim($attributes['name']) : null,
                'type' => $attributes['type'],
                'value' => $attributes['value'],
                'minimum_spend' => filled($attributes['minimum_spend'] ?? null) ? $attributes['minimum_spend'] : 0,
                'start_date' => filled($attributes['start_date'] ?? null) ? $attributes['start_date'] : null,
                'end_date' => filled($attributes['end_date'] ?? null) ? $attributes['end_date'] : null,
                'active' => (bool) ($attributes['active'] ?? true),
                'affiliate_id' => $attributes['affiliate_id'] ?? null,
                'affiliate_commission_value' => filled($attributes['affiliate_commission_value'] ?? null)
                    ? $attributes['affiliate_commission_value']
                    : null,
            ]);

            $coupon->save();

            $countryIds = array_values(array_filter((array) ($attributes['country_ids'] ?? []), 'is_numeric'));
            $coupon->countries()->sync($countryIds);

            return $coupon->fresh();
        });
    }

    public function delete(CentralCoupon $coupon): void
    {
        $coupon->delete();
    }
}
