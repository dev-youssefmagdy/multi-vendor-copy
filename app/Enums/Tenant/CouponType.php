<?php

namespace App\Enums\Tenant;

enum CouponType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
