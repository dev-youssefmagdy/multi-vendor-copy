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
}
