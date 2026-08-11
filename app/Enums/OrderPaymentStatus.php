<?php

namespace App\Enums;

enum OrderPaymentStatus: string
{
    case Pending = 'pending';
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Unpaid = 'unpaid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return __(str($this->value)->headline()->toString());
    }
}
