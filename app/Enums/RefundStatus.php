<?php

namespace App\Enums;

enum RefundStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Processing => __('Processing'),
            self::Completed => __('Completed'),
            self::Failed => __('Failed'),
            self::Rejected => __('Rejected'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Processing => 'blue',
            self::Completed => 'green',
            self::Failed, self::Rejected => 'red',
        };
    }

    /** Statuses whose amount counts against the order's refundable total (no-double-refund guard). */
    public function reservesAmount(): bool
    {
        return in_array($this, [self::Pending, self::Processing, self::Completed], true);
    }

    /** The refund can still be acted on (executed, retried, completed manually or rejected). */
    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Processing, self::Failed], true);
    }

    /** @return list<self> */
    public static function reservingStatuses(): array
    {
        return array_values(array_filter(self::cases(), fn (self $s) => $s->reservesAmount()));
    }
}
