<?php

namespace App\Enums;

/**
 * Why an order was cancelled. Each reason belongs to an audience: reasons a
 * customer can pick on the storefront, reasons staff (vendor / platform admin)
 * can pick, or both (`other`).
 */
enum CancellationReason: string
{
    // Customer reasons
    case ChangedMind = 'changed_mind';
    case OrderedByMistake = 'ordered_by_mistake';
    case FoundBetterPrice = 'found_better_price';
    case DeliveryTooLong = 'delivery_too_long';
    case WrongAddressOrDetails = 'wrong_address_or_details';
    case PaymentIssue = 'payment_issue';
    case DuplicateOrder = 'duplicate_order';

    // Staff (vendor / admin) reasons
    case OutOfStock = 'out_of_stock';
    case UnableToFulfil = 'unable_to_fulfil';
    case SuspectedFraud = 'suspected_fraud';
    case CustomerRequest = 'customer_request';
    case PricingError = 'pricing_error';

    // Shared
    case Other = 'other';

    public const AUDIENCE_CUSTOMER = 'customer';

    public const AUDIENCE_STAFF = 'staff';

    public const AUDIENCE_BOTH = 'both';

    public function label(): string
    {
        return match ($this) {
            self::ChangedMind => __('Changed my mind'),
            self::OrderedByMistake => __('Ordered by mistake'),
            self::FoundBetterPrice => __('Found a better price elsewhere'),
            self::DeliveryTooLong => __('Delivery time is too long'),
            self::WrongAddressOrDetails => __('Wrong address or order details'),
            self::PaymentIssue => __('Payment issue'),
            self::DuplicateOrder => __('Duplicate order'),
            self::OutOfStock => __('Out of stock'),
            self::UnableToFulfil => __('Unable to fulfil the order'),
            self::SuspectedFraud => __('Suspected fraud'),
            self::CustomerRequest => __('Customer request'),
            self::PricingError => __('Pricing error'),
            self::Other => __('Other'),
        };
    }

    /** @return 'customer'|'staff'|'both' */
    public function audience(): string
    {
        return match ($this) {
            self::ChangedMind,
            self::OrderedByMistake,
            self::FoundBetterPrice,
            self::DeliveryTooLong,
            self::WrongAddressOrDetails,
            self::PaymentIssue,
            self::DuplicateOrder => self::AUDIENCE_CUSTOMER,
            self::OutOfStock,
            self::UnableToFulfil,
            self::SuspectedFraud,
            self::CustomerRequest,
            self::PricingError => self::AUDIENCE_STAFF,
            self::Other => self::AUDIENCE_BOTH,
        };
    }

    /** A free-text note is mandatory for this reason. */
    public function requiresNote(): bool
    {
        return $this === self::Other;
    }

    /** Whether the given actor may pick this reason. */
    public function isAllowedFor(CancellationActor $actor): bool
    {
        return in_array($this, self::forActor($actor), true);
    }

    /** @return list<self> */
    public static function forCustomer(): array
    {
        return self::forAudience(self::AUDIENCE_CUSTOMER);
    }

    /** @return list<self> */
    public static function forStaff(): array
    {
        return self::forAudience(self::AUDIENCE_STAFF);
    }

    /**
     * Reasons selectable by an actor. The system actor may use any reason.
     *
     * @return list<self>
     */
    public static function forActor(CancellationActor $actor): array
    {
        return match ($actor) {
            CancellationActor::Customer => self::forCustomer(),
            CancellationActor::Vendor, CancellationActor::Admin => self::forStaff(),
            CancellationActor::System => self::cases(),
        };
    }

    /** @return array<string, string> value => label, ready for a <select>. */
    public static function options(CancellationActor $actor): array
    {
        $options = [];

        foreach (self::forActor($actor) as $reason) {
            $options[$reason->value] = $reason->label();
        }

        return $options;
    }

    /** @return list<self> */
    private static function forAudience(string $audience): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $reason) => in_array($reason->audience(), [$audience, self::AUDIENCE_BOTH], true),
        ));
    }
}
