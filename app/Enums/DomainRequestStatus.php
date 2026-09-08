<?php

namespace App\Enums;

enum DomainRequestStatus: string
{
    case Pending = 'pending';
    case Connected = 'connected';
    case Removed = 'removed';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
