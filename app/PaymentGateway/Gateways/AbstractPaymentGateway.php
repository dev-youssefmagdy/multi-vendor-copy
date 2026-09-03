<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\Contracts\PaymentGatewayInterface;
use App\PaymentGateway\Exceptions\PaymentException;

abstract class AbstractPaymentGateway implements PaymentGatewayInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->boot();
    }

    /**
     * Override in concrete gateways to run initialisation
     * (e.g. set SDK keys) after the config is loaded.
     */
    protected function boot(): void
    {
    }

    /**
     * Retrieve a config value with an optional default.
     */
    protected function cfg(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Assert that required config keys are present.
     *
     * @param string[] $keys
     * @throws PaymentException
     */
    protected function requireConfig(array $keys): void
    {
        foreach ($keys as $key) {
            if (empty($this->config[$key])) {
                throw PaymentException::configMissing($this->getKey(), $key);
            }
        }
    }

    /**
     * Returns true when sandbox / test mode is active.
     */
    protected function isSandbox(): bool
    {
        return (bool) ($this->config['sandbox'] ?? false);
    }
}
