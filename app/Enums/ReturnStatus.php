<?php

namespace App\Enums;

enum ReturnStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::Pending  => 'Pending Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Refunded => 'Refunded',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending  => 'amber',
            self::Approved => 'blue',
            self::Rejected => 'red',
            self::Refunded => 'green',
        };
    }
}
