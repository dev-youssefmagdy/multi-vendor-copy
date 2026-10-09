<?php

namespace App\Enums;

/** Who performed an order action (cancellation, refund request/approval, return review). */
enum CancellationActor: string
{
    case Customer = 'customer';
    case Vendor = 'vendor';
    case Admin = 'admin';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Customer => __('Customer'),
            self::Vendor => __('Store'),
            self::Admin => __('Support'),
            self::System => __('System'),
        };
    }

    /** How the actor is described to the customer on the storefront ("You" / "The store" / "Support"). */
    public function customerFacingLabel(): string
    {
        return match ($this) {
            self::Customer => __('You'),
            self::Vendor => __('The store'),
            self::Admin => __('Support'),
            self::System => __('System'),
        };
    }

    public function isStaff(): bool
    {
        return $this === self::Vendor || $this === self::Admin;
    }
}
