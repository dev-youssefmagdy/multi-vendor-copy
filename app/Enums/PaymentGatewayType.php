<?php

namespace App\Enums;

enum PaymentGatewayType: string
{
    /** Receives customer → vendor order payments on the storefront. */
    case Orders = 'orders';

    /** Processes vendor → central payments (subscriptions, order settlements, manufacturing requests). */
    case VendorPayments = 'vendor_payments';

    public function label(): string
    {
        return match ($this) {
            self::Orders => 'Orders',
            self::VendorPayments => 'Vendor Payments',
        };
    }
}
