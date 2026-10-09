<?php

namespace App\Enums;

enum ReturnType: string
{
    case Return = 'return';
    case Exchange = 'exchange';

    public function label(): string
    {
        return match ($this) {
            self::Return => __('Return & refund'),
            self::Exchange => __('Exchange'),
        };
    }
}
