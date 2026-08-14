<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Tap Payments Gateway
 * Composer: No Composer SDK required — uses direct REST API calls.
 * API Docs: https://developers.tap.company/
 */
class TapGateway extends AbstractPaymentGateway
{
    private const BASE_URL = 'https://api.tap.company/v2';

    public function getKey(): string
    {
        return 'tap';
    }

    protected function boot(): void
    {
        $this->requireConfig(['secret_key']);
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $response = Http::withToken($this->cfg('secret_key'))
            ->post(self::BASE_URL . '/charges', [
                'amount' => $charge->amount,
                'currency' => $charge->currency,
                'customer' => [
                    'first_name' => $charge->name,
                    'email' => $charge->email,
                    'phone' => ['number' => $charge->phone],
                ],
                'source' => ['id' => 'src_all'],
                'redirect' => ['url' => $charge->successUrl],
            ]);

        $data = $response->json();

        if ($response->successful() && !empty($data['transaction']['url'])) {
            return PaymentResult::redirect($data['transaction']['url']);
        }

        return PaymentResult::failure($data['message'] ?? 'Tap payment initiation failed.');
    }

    public function verify(Request $request): PaymentResult
    {
        $id = $request->input('tap_id') ?? $request->input('id');

        if (!$id) {
            return PaymentResult::failure('Missing Tap charge reference.');
        }

        $response = Http::withToken($this->cfg('secret_key'))->get(self::BASE_URL . '/charges/' . $id);
        $data = $response->json();

        if ($response->successful() && ($data['status'] ?? '') === 'CAPTURED') {
            return PaymentResult::success($data['id'], json_encode($data));
        }

        return PaymentResult::failure('Tap payment not completed.');
    }

    public function refund(string $transactionId, float $amount, string $currency, array $context = []): PaymentResult
    {
        $response = Http::withToken($this->cfg('secret_key'))
            ->post(self::BASE_URL . '/refunds', [
                'charge_id' => $transactionId,
                'amount' => $amount,
                'currency' => $currency,
                'reason' => 'requested_by_customer',
            ]);

        $data = $response->json();

        if ($response->successful() && !empty($data['id'])) {
            return PaymentResult::success($data['id'], json_encode($data));
        }

        return PaymentResult::failure($data['message'] ?? 'Tap refund failed.');
    }

    public function createWebhook(): array
    {
        return [
            'status' => 'not_configurable_via_api',
            'note' => 'Configure the webhook endpoint in the Tap dashboard.',
            'url' => route('payment.webhook', 'tap'),
        ];
    }

    public function verifyWebhook(Request $request): bool
    {
        $header = $request->header('hashstring');

        if (!$header) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), (string) $this->cfg('secret_key'));

        return hash_equals($expected, $header);
    }

    protected static function meta(): array
    {
        return [
            'currencies' => ['AED', 'SAR', 'KWD', 'BHD', 'QAR', 'OMR', 'JOD', 'EGP', 'USD'],
            'merchant_countries' => ['AE', 'SA', 'KW', 'BH', 'QA', 'OM', 'JO', 'EG'],
            'customer_countries' => ['AE', 'SA', 'KW', 'BH', 'QA', 'OM', 'JO', 'EG', 'US', 'GB', 'DE', 'FR', 'TR', 'IN', 'PK'],
            'payment_methods' => ['card', 'apple_pay', 'mada', 'knet', 'benefit'],
        ];
    }
}
