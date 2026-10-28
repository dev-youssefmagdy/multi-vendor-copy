<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

/** Server-side cart of a mobile customer: only product/variant/qty (prices are always recomputed). */
class MobileCart extends Model
{
    protected $fillable = ['customer_id', 'items', 'coupon_code'];

    protected function casts(): array
    {
        return ['items' => 'array'];
    }
}
