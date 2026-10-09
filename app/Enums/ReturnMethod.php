<?php

namespace App\Enums;

enum ReturnMethod: string
{
    case CourierPickup = 'courier_pickup';
    case DropOff = 'drop_off';
    case ShipBack = 'ship_back';

    public function label(): string
    {
        return match ($this) {
            self::CourierPickup => __('Courier pickup'),
            self::DropOff => __('Drop off at the store'),
            self::ShipBack => __('Ship it back myself'),
        };
    }
}
