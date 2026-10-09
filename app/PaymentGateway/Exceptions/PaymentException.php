<?php

namespace App\PaymentGateway\Exceptions;

use RuntimeException;

class PaymentException extends RuntimeException
{
    public const GATEWAY_NOT_FOUND = 1;

    public const CONFIG_MISSING = 2;

    public const CHARGE_FAILED = 3;

    public const NOT_SUPPORTED = 4;

    public static function gatewayNotFound(string $key): self
    {
        return new self("Payment gateway [{$key}] is not registered or configured.", self::GATEWAY_NOT_FOUND);
    }

    public static function configMissing(string $key, string $field): self
    {
        return new self("Missing required config field [{$field}] for gateway [{$key}].", self::CONFIG_MISSING);
    }

    public static function chargeFailed(string $gateway, string $reason): self
    {
        return new self("Charge failed on gateway [{$gateway}]: {$reason}", self::CHARGE_FAILED);
    }

    public static function notSupported(string $gateway, string $capability): self
    {
        return new self("Gateway [{$gateway}] does not support [{$capability}].", self::NOT_SUPPORTED);
    }

    public function isNotSupported(): bool
    {
        return $this->getCode() === self::NOT_SUPPORTED;
    }
}
