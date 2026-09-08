<?php

namespace App\Enums;

enum PaymentGatewayMode: string
{
    case Test = 'test';
    case Live = 'live';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
