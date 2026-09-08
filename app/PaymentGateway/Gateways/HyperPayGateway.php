<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * HyperPay Gateway
 * Composer: No Composer SDK required — uses direct REST API calls.
 * API Docs: https://hyperpay.docs.oppwa.com/
 */
class HyperPayGateway extends AbstractPaymentGateway
{
    private string $baseUrl;

    public function getKey(): string
    {
        return 'hyperpay';
    }

    protected function boot(): void
    {
        $this->requireConfig(['access_token', 'entity_id']);
        $this->baseUrl = $this->isSandbox()
            ? 'https://eu-test.oppwa.com'
            : 'https://eu-prod.oppwa.com';
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $response = Http::withToken($this->cfg('access_token'))
            ->asForm()
            ->post("{$this->baseUrl}/v1/checkouts", [
                'entityId' => $this->cfg('entity_id'),
                'amount' => number_format($charge->amount, 2, '.', ''),
                'currency' => $charge->currency,
                'paymentType' => 'DB',
            ]);

        $data = $response->json();

        if ($response->successful() && !empty($data['id'])) {
            return PaymentResult::view('payments.hyperpay', [
                'checkout_id' => $data['id'],
                'entity_id' => $this->cfg('entity_id'),
                'sandbox' => $this->isSandbox(),
            ]);
        }

        return PaymentResult::failure($data['result']['description'] ?? 'HyperPay checkout initiation failed.');
    }

    public function verify(Request $request): PaymentResult
    {
        $id = $request->input('id');

        if (!$id) {
            return PaymentResult::failure('Missing HyperPay checkout reference.');
        }

        $response = Http::withToken($this->cfg('access_token'))
            ->get("{$this->baseUrl}/v1/checkouts/{$id}/payment", ['entityId' => $this->cfg('entity_id')]);

        $data = $response->json();
        $code = $data['result']['code'] ?? '';

        if (preg_match('/^(000\.000\.|000\.100\.1|000\.[36])/', $code)) {
            return PaymentResult::success($id, json_encode($data));
        }

        return PaymentResult::failure($data['result']['description'] ?? 'HyperPay payment not completed.');
    }

    public function refund(string $transactionId, float $amount, string $currency, array $context = []): PaymentResult
    {
        $response = Http::withToken($this->cfg('access_token'))
            ->asForm()
            ->post("{$this->baseUrl}/v1/payments/{$transactionId}", [
                'entityId' => $this->cfg('entity_id'),
                'amount' => number_format($amount, 2, '.', ''),
                'currency' => $currency,
                'paymentType' => 'RF',
            ]);

        $data = $response->json();
        $code = $data['result']['code'] ?? '';

        if (preg_match('/^(000\.000\.|000\.100\.1|000\.[36])/', $code)) {
            return PaymentResult::success($data['id'] ?? $transactionId, json_encode($data));
        }

        return PaymentResult::failure($data['result']['description'] ?? 'HyperPay refund failed.');
    }

    public function createWebhook(): array
    {
        return [
            'status' => 'not_configurable_via_api',
            'note' => 'Configure the webhook endpoint in the HyperPay merchant dashboard.',
            'url' => route('payment.webhook', 'hyperpay'),
        ];
    }

    public function verifyWebhook(Request $request): bool
    {
        return hash_equals((string) ($this->cfg('webhook_secret') ?? ''), (string) ($request->header('X-Webhook-Secret') ?? ''));
    }

    protected static function meta(): array
    {
        return [
            'currencies' => ['SAR', 'AED', 'USD', 'EUR'],
            'merchant_countries' => ['SA', 'AE', 'JO', 'EG'],
            'customer_countries' => ['SA', 'AE', 'JO', 'EG', 'KW', 'BH', 'QA', 'OM', 'US', 'GB', 'DE', 'FR', 'TR', 'IN', 'PK'],
            'payment_methods' => ['card', 'mada', 'apple_pay'],
        ];
    }
}
