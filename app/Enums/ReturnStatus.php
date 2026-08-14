<?php

namespace App\Enums;

enum ReturnStatus: string
{
    case Pending = 'pending';
    case AwaitingMerchantReview = 'awaiting_merchant_review';
    case AwaitingInfo = 'awaiting_info';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case ItemReceived = 'item_received';
    case Refunded = 'refunded';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Review',
            self::AwaitingMerchantReview => 'Awaiting Merchant Review',
            self::AwaitingInfo => 'Awaiting Customer Info',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::ItemReceived => 'Item Received',
            self::Refunded => 'Refunded',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::AwaitingMerchantReview => 'amber',
            self::AwaitingInfo => 'amber',
            self::Approved => 'blue',
            self::Rejected => 'red',
            self::ItemReceived => 'blue',
            self::Refunded => 'green',
            self::Closed => 'gray',
        };
    }

    /** Statuses that are not a dead end — the request can still transition further. */
    public function isOpen(): bool
    {
        return !in_array($this, [self::Rejected, self::Refunded, self::Closed], true);
    }
}
