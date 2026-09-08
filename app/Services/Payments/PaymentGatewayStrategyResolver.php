<?php

namespace App\Services\Payments;

use App\Services\Payments\Contracts\PaymentGatewayStrategy;
use App\Services\Payments\Strategies\GenericPaymentGatewayStrategy;
use App\Services\Payments\Strategies\PaypalPaymentGatewayStrategy;
use App\Services\Payments\Strategies\StripePaymentGatewayStrategy;

class PaymentGatewayStrategyResolver
{
    public function resolve(string $code, ?string $name = null): PaymentGatewayStrategy
    {
        return match ($code) {
            'stripe' => new StripePaymentGatewayStrategy(),
            'paypal' => new PaypalPaymentGatewayStrategy(),
            default => new GenericPaymentGatewayStrategy($code, $name ?? str($code)->headline()->toString()),
        };
    }
}
