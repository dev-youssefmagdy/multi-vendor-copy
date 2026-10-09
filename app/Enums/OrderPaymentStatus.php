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
    case PartiallyRefunded = 'partially_refunded';

    public function label(): string
    {
        return __(str($this->value)->headline()->toString());
    }

    public function color(): string
    {
        return match ($this) {
            self::Paid => 'green',
            self::Pending, self::PendingPayment, self::Unpaid => 'amber',
            self::Failed => 'red',
            self::Refunded, self::PartiallyRefunded => 'violet',
        };
    }
}
