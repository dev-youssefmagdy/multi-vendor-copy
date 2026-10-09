<?php

namespace App\Enums;

enum RefundSource: string
{
    case Cancellation = 'cancellation';
    case Return = 'return';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Cancellation => __('Order cancellation'),
            self::Return => __('Return'),
            self::Manual => __('Manual refund'),
        };
    }
}
