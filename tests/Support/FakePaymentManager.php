<?php

declare(strict_types=1);

namespace Tests\Support;

use App\PaymentGateway\Contracts\PaymentGatewayInterface;
use App\PaymentGateway\PaymentManager;

/**
 * PaymentManager that returns a FakePaymentGateway for the faked keys and resolves every
 * other key normally. Installed by FakePaymentGateway::install().
 */
class FakePaymentManager extends PaymentManager
{
    /** @param list<string> $keys */
    public function __construct(
        public readonly FakePaymentGateway $fake,
        private readonly array $keys,
    ) {}

    public function gateway(string $key): PaymentGatewayInterface
    {
        return $this->fakes($key) ? $this->fake : parent::gateway($key);
    }

    public function supportsRefunds(string $key): bool
    {
        return $this->fakes($key) ? $this->fake->advertisesRefunds : parent::supportsRefunds($key);
    }

    public function fakes(string $key): bool
    {
        return in_array($key, $this->keys, true);
    }
}
