<?php

namespace App\Enums\Tenant;

enum SubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
