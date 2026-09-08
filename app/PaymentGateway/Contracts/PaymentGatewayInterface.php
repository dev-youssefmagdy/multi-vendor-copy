<?php

namespace App\PaymentGateway\Contracts;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Initiate a payment charge.
     * Returns a PaymentResult that may instruct the caller
     * to redirect, render a view, or report an inline error.
     */
    public function charge(PaymentCharge $charge): PaymentResult;

    /**
     * Verify / handle the success callback from the gateway.
     * Returns a PaymentResult with the confirmed transaction ID.
     */
    public function verify(Request $request): PaymentResult;

    /**
     * The unique key bound in config/payment-gateways.php (e.g. "stripe").
     */
    public function getKey(): string;

    /**
     * Refund a previously captured transaction (full or partial).
     */
    public function refund(string $transactionId, float $amount, string $currency, array $context = []): PaymentResult;

    /**
     * Register/describe the inbound webhook for this gateway.
     * Returns a descriptor, e.g. ['url' => ..., 'events' => [...]].
     */
    public function createWebhook(): array;

    /**
     * Verify an inbound webhook request's signature.
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * ISO currency codes this gateway can settle in.
     */
    public function getSupportedCurrencies(): array;

    /**
     * ISO2 country codes merchants can onboard/register from.
     */
    public function getSupportedCountries(): array;

    /**
     * ISO2 country codes customers can pay from.
     */
    public function getCustomerCountries(): array;
}
