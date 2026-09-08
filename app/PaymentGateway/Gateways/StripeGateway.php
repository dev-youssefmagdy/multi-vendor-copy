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

    public function refund(string $transactionId, float $amount, string $currency, array $context = []): PaymentResult
    {
        try {
            Stripe::setApiKey($this->cfg('secret'));

            $refund = \Stripe\Refund::create([
                'charge' => $transactionId,
                'amount' => (int) round($amount * 100),
            ]);

            return PaymentResult::success($refund->id, json_encode($refund->toArray()));
        } catch (\Throwable $e) {
            return PaymentResult::failure($e->getMessage());
        }
    }

    public function createWebhook(): array
    {
        return [
            'url' => route('payment.webhook', 'stripe'),
            'events' => ['charge.succeeded', 'charge.refunded', 'charge.failed'],
        ];
    }

    public function verifyWebhook(Request $request): bool
    {
        try {
            \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                $this->cfg('webhook_secret')
            );

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected static function meta(): array
    {
        return [
            'currencies' => ['USD', 'EUR', 'GBP', 'AED', 'SAR', 'EGP', 'CAD', 'AUD', 'SGD', 'INR'],
            'merchant_countries' => ['US', 'GB', 'CA', 'AU', 'IE', 'FR', 'DE', 'ES', 'IT', 'NL', 'SE', 'SG', 'JP', 'AE'],
            'customer_countries' => ['US', 'GB', 'CA', 'AU', 'DE', 'FR', 'ES', 'IT', 'NL', 'SE', 'AE', 'SA', 'SG', 'JP', 'IN', 'EG', 'BR', 'MX', 'ZA', 'NZ'],
            'payment_methods' => ['card', 'apple_pay', 'google_pay', 'wallets'],
        ];
    }
}
