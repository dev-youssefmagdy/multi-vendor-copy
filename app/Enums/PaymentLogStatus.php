<?php

namespace App\Enums;

enum PaymentLogStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
