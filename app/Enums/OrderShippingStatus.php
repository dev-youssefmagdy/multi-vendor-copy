<?php

namespace App\Enums;

enum OrderShippingStatus: string
{
    case Pending = 'pending';
    case InDelivery = 'in_delivery';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __(str($this->value)->replace('_', ' ')->headline()->toString());
    }
}
