<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

/**
 * Moyasar Gateway
 * Composer: No Composer SDK required — uses direct REST API calls.
 * API Docs: https://docs.moyasar.com/
 */
class MoyasarGateway extends AbstractPaymentGateway
{
    private const BASE_URL = 'https://api.moyasar.com/v1';

    public function getKey(): string
    {
        return 'moyasar';
    }

    protected function boot(): void
    {
        $this->requireConfig(['secret_key']);
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $response = Http::withBasicAuth($this->cfg('secret_key'), '')
            ->post(self::BASE_URL . '/invoices', [
                'amount' => (int) round($charge->amount * 100),
                'currency' => $charge->currency ?: 'SAR',
                'description' => $charge->description,
                'callback_url' => $charge->successUrl,
            ]);

        $data = $response->json();

        if ($response->successful() && !empty($data['url'])) {
            Session::put('moyasar_invoice_id', $data['id']);
            return PaymentResult::redirect($data['url']);
        }

        return PaymentResult::failure($data['message'] ?? 'Moyasar payment initiation failed.');
    }

    public function verify(Request $request): PaymentResult
    {
        $id = $request->input('id') ?? Session::get('moyasar_invoice_id');

        if (!$id) {
            return PaymentResult::failure('Missing Moyasar invoice reference.');
        }

        $response = Http::withBasicAuth($this->cfg('secret_key'), '')->get(self::BASE_URL . '/invoices/' . $id);
        $data = $response->json();

        if ($response->successful() && ($data['status'] ?? '') === 'paid') {
            Session::forget('moyasar_invoice_id');
            return PaymentResult::success($data['id'], json_encode($data));
        }

        return PaymentResult::failure('Moyasar payment not completed.');
    }

    public function refund(string $transactionId, float $amount, string $currency, array $context = []): PaymentResult
    {
        $response = Http::withBasicAuth($this->cfg('secret_key'), '')
            ->post(self::BASE_URL . '/payments/' . $transactionId . '/refund', [
                'amount' => (int) round($amount * 100),
            ]);

        $data = $response->json();

        if ($response->successful() && !empty($data['id'])) {
            return PaymentResult::success($data['id'], json_encode($data));
        }

        return PaymentResult::failure($data['message'] ?? 'Moyasar refund failed.');
    }

    public function createWebhook(): array
    {
        return [
            'status' => 'not_configurable_via_api',
            'note' => 'Configure the webhook endpoint in the Moyasar dashboard.',
            'url' => route('payment.webhook', 'moyasar'),
        ];
    }

    public function verifyWebhook(Request $request): bool
    {
        $secret = (string) ($this->cfg('webhook_secret') ?? '');
        $token = (string) ($request->header('Authorization') ?? $request->input('secret_token') ?? '');

        return $secret !== '' && hash_equals($secret, $token);
    }

    protected static function meta(): array
    {
        return [
            'currencies' => ['SAR'],
            'merchant_countries' => ['SA'],
            'customer_countries' => ['SA', 'AE', 'KW', 'BH', 'QA', 'OM', 'US', 'GB', 'DE', 'FR', 'EG', 'JO', 'TR', 'IN', 'PK'],
            'payment_methods' => ['card', 'mada', 'apple_pay', 'stc_pay'],
        ];
    }
}
