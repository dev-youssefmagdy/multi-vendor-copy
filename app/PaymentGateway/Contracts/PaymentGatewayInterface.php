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
}
