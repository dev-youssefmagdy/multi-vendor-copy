<?php

namespace App\Services\Payments\Strategies;

use App\Services\Payments\Contracts\PaymentGatewayStrategy;

abstract class AbstractPaymentGatewayStrategy implements PaymentGatewayStrategy
{
    public function validateCredentials(array $credentials): array
    {
        $missing = collect($this->requiredCredentials())
            ->filter(fn (string $key) => blank($credentials[$key] ?? null))
            ->values()
            ->all();

        return [
            'valid' => $missing === [],
            'missing' => $missing,
            'required' => $this->requiredCredentials(),
        ];
    }

    public function initiate(array $payload): array
    {
        return [
            'ok' => true,
            'gateway' => $this->code(),
            'payload' => $payload,
        ];
    }

    public function refund(array $payload): array
    {
        return [
            'ok' => true,
            'gateway' => $this->code(),
            'payload' => $payload,
        ];
    }
}
