<?php

namespace App\Enums;

enum ActivationStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
