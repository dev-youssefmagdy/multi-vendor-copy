<?php

namespace App\Services\Payments\Strategies;

class StripePaymentGatewayStrategy extends AbstractPaymentGatewayStrategy
{
    public function code(): string
    {
        return 'stripe';
    }

    public function displayName(): string
    {
        return 'Stripe';
    }

    public function requiredCredentials(): array
    {
        return ['key', 'secret'];
    }
}
