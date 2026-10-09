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
    case Inspected = 'inspected';
    case Refunded = 'refunded';
    case ExchangeShipped = 'exchange_shipped';
    case Exchanged = 'exchanged';
    case Cancelled = 'cancelled';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending Review'),
            self::AwaitingMerchantReview => __('Awaiting Merchant Review'),
            self::AwaitingInfo => __('Awaiting Customer Info'),
            self::Approved => __('Approved'),
            self::Rejected => __('Rejected'),
            self::ItemReceived => __('Item Received'),
            self::Inspected => __('Inspected'),
            self::Refunded => __('Refunded'),
            self::ExchangeShipped => __('Replacement Shipped'),
            self::Exchanged => __('Exchanged'),
            self::Cancelled => __('Withdrawn'),
            self::Closed => __('Closed'),
        };
    }

    /**
     * Badge colour. Consumers (admin/vendor/storefront badges) map exactly
     * green/blue/red/gray and fall back to amber, so stay within that palette.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::AwaitingMerchantReview => 'amber',
            self::AwaitingInfo => 'amber',
            self::Approved => 'blue',
            self::Rejected => 'red',
            self::ItemReceived => 'blue',
            self::Inspected => 'blue',
            self::Refunded => 'green',
            self::ExchangeShipped => 'blue',
            self::Exchanged => 'green',
            self::Cancelled => 'gray',
            self::Closed => 'gray',
        };
    }

    /** Statuses that are not a dead end — the request can still transition further. */
    public function isOpen(): bool
    {
        return ! in_array($this, [
            self::Rejected,
            self::Refunded,
            self::Exchanged,
            self::Cancelled,
            self::Closed,
        ], true);
    }

    /**
     * The return state machine (see RETURN_EXCHANGE_REFUND_PLAN.md B.5).
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [
                self::AwaitingMerchantReview,
                self::AwaitingInfo,
                self::Approved,
                self::Rejected,
                self::Cancelled,
            ],
            self::AwaitingMerchantReview => [
                self::AwaitingInfo,
                self::Approved,
                self::Rejected,
                self::Cancelled,
            ],
            self::AwaitingInfo => [
                self::Pending,                  // customer replied
                self::AwaitingMerchantReview,   // customer replied on a request forwarded to the merchant
                self::Approved,
                self::Rejected,
                self::Cancelled,
            ],
            self::Approved => [
                self::ItemReceived,
                self::Rejected,                 // e.g. the item never arrived
            ],
            self::ItemReceived => [
                self::Inspected,
            ],
            self::Inspected => [
                self::Refunded,
                self::ExchangeShipped,
                self::Rejected,                 // inspection failed
            ],
            self::ExchangeShipped => [
                self::Exchanged,
            ],
            self::Refunded,
            self::Exchanged,
            self::Rejected,
            self::Cancelled => [
                self::Closed,
            ],
            self::Closed => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** The customer may still withdraw the request. */
    public function canBeWithdrawn(): bool
    {
        return $this->canTransitionTo(self::Cancelled);
    }

    /**
     * Statuses that hold (part of) an order item's quantity — every status except
     * the ones where the goods were never taken back (rejected / withdrawn).
     *
     * @return list<self>
     */
    public static function quantityHoldingStatuses(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $status) => ! in_array($status, [self::Rejected, self::Cancelled], true),
        ));
    }

    /** @return list<self> */
    public static function openStatuses(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status) => $status->isOpen()));
    }
}
