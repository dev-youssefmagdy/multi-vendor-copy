<?php

namespace App\Services\Payments\Strategies;

class GenericPaymentGatewayStrategy extends AbstractPaymentGatewayStrategy
{
    public function __construct(
        protected string $gatewayCode,
        protected string $gatewayName = 'Custom Gateway'
    ) {
    }

    public function code(): string
    {
        return $this->gatewayCode;
    }

    public function displayName(): string
    {
        return $this->gatewayName;
    }

    public function requiredCredentials(): array
    {
        return [];
    }
}
