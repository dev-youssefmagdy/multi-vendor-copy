<?php

namespace App\PaymentGateway\Exceptions;

use RuntimeException;

class PaymentException extends RuntimeException
{
    public static function gatewayNotFound(string $key): self
    {
        return new self("Payment gateway [{$key}] is not registered or configured.");
    }

    public static function configMissing(string $key, string $field): self
    {
        return new self("Missing required config field [{$field}] for gateway [{$key}].");
    }

    public static function chargeFailed(string $gateway, string $reason): self
    {
        return new self("Charge failed on gateway [{$gateway}]: {$reason}");
    }

    public static function notSupported(string $gateway, string $capability): self
    {
        return new self("Gateway [{$gateway}] does not support [{$capability}].");
    }
}
