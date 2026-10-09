<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';
    case Refunded = 'refunded';

    public function label(): string
    {
        return str($this->value)->headline()->toString();

        return __(str($this->value)->headline()->toString());
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Processing => 'orange',
            self::Shipped => 'blue',
            self::Delivered, self::Completed => 'green',
            self::Cancelled, self::Rejected => 'red',
            self::Refunded => 'violet',
        };
    }

    /** Cancelled or rejected by the vendor — the order will never be fulfilled. */
    public function isCancelled(): bool
    {
        return in_array($this, [self::Cancelled, self::Rejected], true);
    }

    /** The goods reached the customer (Completed is treated like Delivered). */
    public function isDelivered(): bool
    {
        return in_array($this, [self::Delivered, self::Completed], true);
    }

    /** No further fulfilment transitions are possible. */
    public function isTerminal(): bool
    {
        return $this->isCancelled() || $this === self::Refunded;
    }
}
