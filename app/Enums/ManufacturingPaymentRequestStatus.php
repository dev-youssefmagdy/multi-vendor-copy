<?php

namespace App\Enums;

enum ManufacturingPaymentRequestStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Payment',
            self::Paid => 'Paid',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'badge badge-amber',
            self::Paid => 'badge badge-green',
            self::Cancelled => 'badge badge-red',
        };
    }
}
