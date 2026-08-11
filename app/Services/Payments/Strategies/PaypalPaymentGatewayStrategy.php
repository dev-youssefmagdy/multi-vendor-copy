<?php

namespace App\Services\Payments\Strategies;

class PaypalPaymentGatewayStrategy extends AbstractPaymentGatewayStrategy
{
    public function code(): string
    {
        return 'paypal';
    }

    public function displayName(): string
    {
        return 'PayPal';
    }

    public function requiredCredentials(): array
    {
        return ['client_id', 'secret'];
    }
}
