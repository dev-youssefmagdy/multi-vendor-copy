<?php

namespace App\Enums;

enum RefundMethod: string
{
    /** Refunded through the gateway that captured the original payment. */
    case OriginalPayment = 'original_payment';
    /** Settled outside the system (bank transfer, cash, store credit) — used for COD or unsupported gateways. */
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::OriginalPayment => __('Original payment method'),
            self::Manual => __('Manual (bank transfer / cash)'),
        };
    }
}
