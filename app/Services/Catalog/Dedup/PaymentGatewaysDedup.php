<?php

namespace App\Services\Catalog\Dedup;

/** `catalog:dedup-migrate payment_gateways` (dedup plan phase 6): see ConfigDedup. */
final class PaymentGatewaysDedup extends ConfigDedup
{
    protected function section(): string
    {
        return 'payment-gateways';
    }
}
