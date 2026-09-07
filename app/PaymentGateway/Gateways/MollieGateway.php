<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Mollie\Laravel\Facades\Mollie;

/**
 * Mollie Gateway
 * Composer: composer require mollie/laravel-mollie
 * Publish config: php artisan vendor:publish --provider="Mollie\Laravel\MollieServiceProvider"
 */
class MollieGateway extends AbstractPaymentGateway
{
    public function getKey(): string
    {
        return 'mollie';
    }

    protected function boot(): void
    {
        $this->requireConfig(['key']);
        Config::set('mollie.key', $this->cfg('key'));
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        try {
            $payment = Mollie::api()->payments()->create([
                'amount' => [
                    'currency' => $charge->currency,
                    'value'    => sprintf('%0.2f', $charge->amount),
                ],
                'description' => $charge->description,
                'redirectUrl' => $charge->successUrl,
                'metadata'    => array_merge(['order_id' => $charge->orderId], $charge->metadata),
            ]);

            Session::put('mollie_payment_id', $payment->id);

            return PaymentResult::redirect($payment->getCheckoutUrl());
        } catch (\Throwable $e) {
            return PaymentResult::failure($e->getMessage());
        }
    }

    public function verify(Request $request): PaymentResult
    {
        $paymentId = Session::get('mollie_payment_id');

        if (!$paymentId) {
            return PaymentResult::failure('Mollie payment ID missing from session.');
        }

        try {
            $payment = Mollie::api()->payments()->get($paymentId);

            if ($payment->status === 'paid') {
                Session::forget('mollie_payment_id');
                return PaymentResult::success($payment->id, json_encode($payment));
            }

            return PaymentResult::failure('Mollie payment status: ' . $payment->status);
        } catch (\Throwable $e) {
            return PaymentResult::failure($e->getMessage());
        }
    }
}
