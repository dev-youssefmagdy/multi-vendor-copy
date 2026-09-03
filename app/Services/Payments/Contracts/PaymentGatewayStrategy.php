<?php

namespace App\Services\Payments\Contracts;

interface PaymentGatewayStrategy
{
    public function code(): string;

    public function displayName(): string;

    public function requiredCredentials(): array;

    public function validateCredentials(array $credentials): array;

    public function initiate(array $payload): array;

    public function refund(array $payload): array;
}
