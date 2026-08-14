<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Amazon Payment Services (APS / PayFort) Gateway
 * Composer: No Composer SDK required — uses direct REST API calls.
 * API Docs: https://paymentservices.amazon.com/docs/EN/index.html
 */
class AmazonPaymentServicesGateway extends AbstractPaymentGateway
{
    private string $baseUrl;

    public function getKey(): string
    {
        return 'amazon_payment_services';
    }

    protected function boot(): void
    {
        $this->requireConfig(['access_code', 'merchant_identifier', 'sha_request_phrase', 'sha_response_phrase']);
        $this->baseUrl = $this->isSandbox()
            ? 'https://sbcheckout.payfort.com/FortAPI'
            : 'https://checkout.payfort.com/FortAPI';
    }

    private function sign(array $params, string $phrase): string
    {
        unset($params['signature']);
        ksort($params);

        $concatenated = $phrase;
        foreach ($params as $key => $value) {
            $concatenated .= "{$key}={$value}";
        }
        $concatenated .= $phrase;

        return hash('sha256', $concatenated);
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $params = [
            'merchant_identifier' => $this->cfg('merchant_identifier'),
            'access_code' => $this->cfg('access_code'),
            'merchant_reference' => $charge->orderId ?? uniqid('aps_'),
            'amount' => (int) round($charge->amount * 100),
            'currency' => $charge->currency,
            'language' => 'en',
            'command' => 'PURCHASE',
            'return_url' => $charge->successUrl,
        ];

        $params['signature'] = $this->sign($params, $this->cfg('sha_request_phrase'));

        return PaymentResult::view('payments.aps', [
            'params' => $params,
            'action_url' => $this->baseUrl . '/paymentPage',
        ]);
    }

    public function verify(Request $request): PaymentResult
    {
        $params = $request->all();
        $signature = $params['signature'] ?? null;
        unset($params['signature']);

        $expected = $this->sign($params, $this->cfg('sha_response_phrase'));

        if (!$signature || !hash_equals($expected, $signature)) {
            return PaymentResult::failure('Amazon Payment Services signature verification failed.');
        }

        if (str_starts_with((string) ($request->input('response_code') ?? ''), '14')) {
            return PaymentResult::success($request->input('fort_id', $request->input('merchant_reference')), json_encode($params));
        }

        return PaymentResult::failure('Amazon Payment Services payment not completed.');
    }

    public function refund(string $transactionId, float $amount, string $currency, array $context = []): PaymentResult
    {
        $params = [
            'merchant_identifier' => $this->cfg('merchant_identifier'),
            'access_code' => $this->cfg('access_code'),
            'merchant_reference' => $transactionId,
            'amount' => (int) round($amount * 100),
            'currency' => $currency,
            'command' => 'REFUND',
        ];

        $params['signature'] = $this->sign($params, $this->cfg('sha_request_phrase'));

        $response = Http::post("{$this->baseUrl}/paymentApi", $params);
        $data = $response->json();

        $signature = $data['signature'] ?? null;
        $toVerify = $data;
        unset($toVerify['signature']);
        $expected = $signature ? $this->sign($toVerify, $this->cfg('sha_response_phrase')) : null;

        if ($signature && hash_equals($expected, $signature) && str_starts_with((string) ($data['response_code'] ?? ''), '14')) {
            return PaymentResult::success($data['fort_id'] ?? $transactionId, json_encode($data));
        }

        return PaymentResult::failure($data['response_message'] ?? 'Amazon Payment Services refund failed.');
    }

    public function createWebhook(): array
    {
        return [
            'status' => 'not_configurable_via_api',
            'note' => 'APS async notifications are configured via merchant dashboard return_url/notification settings.',
            'url' => route('payment.webhook', 'amazon_payment_services'),
        ];
    }

    public function verifyWebhook(Request $request): bool
    {
        $params = $request->all();
        $signature = $params['signature'] ?? null;
        unset($params['signature']);

        if (!$signature) {
            return false;
        }

        $expected = $this->sign($params, $this->cfg('sha_response_phrase'));

        return hash_equals($expected, $signature);
    }

    protected static function meta(): array
    {
        return [
            'currencies' => ['SAR', 'AED', 'EGP', 'USD', 'JOD'],
            'merchant_countries' => ['SA', 'AE', 'EG', 'JO', 'QA'],
            'customer_countries' => ['SA', 'AE', 'EG', 'JO', 'QA', 'KW', 'BH', 'OM', 'US', 'GB', 'DE', 'FR', 'TR', 'IN', 'PK'],
            'payment_methods' => ['card', 'mada', 'apple_pay'],
        ];
    }
}
