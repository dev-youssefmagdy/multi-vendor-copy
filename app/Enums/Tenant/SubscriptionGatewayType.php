<?php

namespace App\Enums\Tenant;

enum SubscriptionGatewayType: string
{
    case Central = 'central';
    case Tenant = 'tenant';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
