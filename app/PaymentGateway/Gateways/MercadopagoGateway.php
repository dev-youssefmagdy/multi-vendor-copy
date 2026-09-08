<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

/**
 * MercadoPago Gateway
 * No official PHP SDK required — uses REST API v1.
 * Alternatively: composer require mercadopago/dx-php
 * API Docs: https://www.mercadopago.com.br/developers/en/docs
 */
class MercadopagoGateway extends AbstractPaymentGateway
{
    private const BASE_URL_LIVE    = 'https://api.mercadopago.com/checkout/preferences';
    private const BASE_URL_SANDBOX = 'https://api.mercadopago.com/checkout/preferences';  // Same URL; sandbox via access_token

    public function getKey(): string
    {
        return 'mercadopago';
    }

    protected function boot(): void
    {
        $this->requireConfig(['access_token']);
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $payload = [
            'items' => [[
                'id'          => $charge->orderId ?? uniqid('mp_'),
                'title'       => $charge->description,
                'quantity'    => 1,
                'currency_id' => $charge->currency,
                'unit_price'  => round($charge->amount, 2),
            ]],
            'payer'      => ['email' => $charge->email ?? 'test_user@example.com'],
            'back_urls'  => [
                'success' => $charge->successUrl,
                'pending' => $charge->successUrl,
                'failure' => $charge->cancelUrl,
            ],
            'auto_return'       => 'approved',
            'notification_url'  => $charge->successUrl,
        ];

        $response = Http::withToken($this->cfg('access_token'))
            ->post(self::BASE_URL_LIVE, $payload);

        $data = $response->json();

        $checkoutUrl = $this->isSandbox()
            ? ($data['sandbox_init_point'] ?? null)
            : ($data['init_point'] ?? null);

        if ($checkoutUrl) {
            Session::put('mercadopago_preference_id', $data['id'] ?? null);
            return PaymentResult::redirect($checkoutUrl);
        }

        return PaymentResult::failure($data['message'] ?? 'MercadoPago preference creation failed.');
    }

    public function verify(Request $request): PaymentResult
    {
        $paymentId = $request->input('payment_id');
        $status    = $request->input('status');

        if (!$paymentId) {
            return PaymentResult::failure('Missing MercadoPago payment_id in callback.');
        }

        if ($status === 'approved') {
            Session::forget('mercadopago_preference_id');
            return PaymentResult::success((string) $paymentId);
        }

        return PaymentResult::failure('MercadoPago payment status: ' . ($status ?? 'unknown'));
    }

    protected static function meta(): array
    {
        return [
            'currencies' => ['ARS', 'BRL', 'CLP', 'COP', 'MXN', 'PEN', 'UYU'],
            'merchant_countries' => ['AR', 'BR', 'CL', 'CO', 'MX', 'PE', 'UY'],
            'customer_countries' => ['AR', 'BR', 'CL', 'CO', 'MX', 'PE', 'UY'],
            'payment_methods' => ['card', 'pix', 'ticket', 'wallet'],
        ];
    }
}
