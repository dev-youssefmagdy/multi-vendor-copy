<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Exception\ApiErrorException;

/**
 * Stripe Gateway
 * Composer: composer require stripe/stripe-php
 */
class StripeGateway extends AbstractPaymentGateway
{
    public function getKey(): string
    {
        return 'stripe';
    }

    protected function boot(): void
    {
        $this->requireConfig(['key', 'secret']);
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        // Stripe charges require a stripeToken from Stripe.js on the frontend.
        // The token is stored in session by Livewire before the redirect.
        $token = request('stripeToken') ?? session('pgtoken_stripe_stripeToken');

        if (empty($token)) {
            return PaymentResult::failure('Stripe token not found in request.');
        }

        session()->forget('pgtoken_stripe_stripeToken');

        try {
            Stripe::setApiKey($this->cfg('secret'));

            $response = Charge::create([
                'source' => $token,
                'currency' => strtolower($charge->currency),
                'amount' => (int) round($charge->amount * 100), // in cents
                'description' => $charge->description,
                'receipt_email' => $charge->email,
                'metadata' => array_merge(['name' => $charge->name ?? ''], $charge->metadata ?? []),
            ]);

            if ($response->status === 'succeeded') {
                return PaymentResult::success($response->id, json_encode($response->toArray()));
            }

            return PaymentResult::failure('Stripe charge did not succeed.');
        } catch (ApiErrorException $e) {
            return PaymentResult::failure($e->getMessage());
        } catch (\Throwable $e) {
            return PaymentResult::failure($e->getMessage());
        }
    }

    public function verify(Request $request): PaymentResult
    {
        return PaymentResult::failure('Use charge() for synchronous Stripe payments.');
    }
}
