<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\Contracts\PaymentGatewayInterface;
use App\PaymentGateway\DTOs\PaymentResult;
use App\PaymentGateway\Exceptions\PaymentException;
use Illuminate\Http\Request;

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

    /**
     * Static capability metadata for this gateway. Override in concrete
     * gateways — must be readable without a constructed instance (i.e.
     * without credentials), which is why this is static.
     *
     * @return array{currencies: string[], merchant_countries: string[], customer_countries: string[], payment_methods: string[]}
     */
    protected static function meta(): array
    {
        return [
            'currencies' => [],
            'merchant_countries' => [],
            'customer_countries' => [],
            'payment_methods' => [],
        ];
    }

    public function refund(string $transactionId, float $amount, string $currency, array $context = []): PaymentResult
    {
        throw PaymentException::notSupported($this->getKey(), 'refund');
    }

    public function createWebhook(): array
    {
        throw PaymentException::notSupported($this->getKey(), 'webhook');
    }

    public function verifyWebhook(Request $request): bool
    {
        throw PaymentException::notSupported($this->getKey(), 'webhook');
    }

    public function getSupportedCurrencies(): array
    {
        return static::meta()['currencies'] ?? [];
    }

    public function getSupportedCountries(): array
    {
        return static::meta()['merchant_countries'] ?? [];
    }

    public function getCustomerCountries(): array
    {
        return static::meta()['customer_countries'] ?? [];
    }
}
