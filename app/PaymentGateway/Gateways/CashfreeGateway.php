<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Cashfree Gateway
 * Composer: No Composer SDK required — uses direct REST API calls.
 * API Docs: https://docs.cashfree.com/
 */
class CashfreeGateway extends AbstractPaymentGateway
{
    private string $baseUrl;

    public function getKey(): string
    {
        return 'cashfree';
    }

    protected function boot(): void
    {
        $this->requireConfig(['app_id', 'secret_key']);
        $this->baseUrl = $this->isSandbox()
            ? 'https://sandbox.cashfree.com/pg'
            : 'https://api.cashfree.com/pg';
    }

    private function headers(): array
    {
        return [
            'x-client-id' => $this->cfg('app_id'),
            'x-client-secret' => $this->cfg('secret_key'),
            'x-api-version' => '2023-08-01',
        ];
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/orders", [
                'order_id' => $charge->orderId ?? uniqid('cf_'),
                'order_amount' => $charge->amount,
                'order_currency' => $charge->currency,
                'customer_details' => [
                    'customer_id' => 'cust_' . uniqid(),
                    'customer_email' => $charge->email,
                    'customer_phone' => $charge->phone ?? '9999999999',
                ],
                'order_meta' => [
                    'return_url' => $charge->successUrl,
                ],
            ]);

        $data = $response->json();

        if ($response->successful() && !empty($data['payment_session_id'])) {
            return PaymentResult::view('payments.cashfree', [
                'payment_session_id' => $data['payment_session_id'],
                'order_id' => $data['order_id'],
            ]);
        }

        return PaymentResult::failure($data['message'] ?? 'Cashfree order creation failed.');
    }

    public function verify(Request $request): PaymentResult
    {
        $orderId = $request->input('order_id');

        if (!$orderId) {
            return PaymentResult::failure('Missing Cashfree order reference.');
        }

        $response = Http::withHeaders($this->headers())->get("{$this->baseUrl}/orders/{$orderId}/payments");
        $payments = $response->json();

        if ($response->successful() && is_array($payments)) {
            foreach ($payments as $payment) {
                if (($payment['payment_status'] ?? '') === 'SUCCESS') {
                    return PaymentResult::success($payment['cf_payment_id'], json_encode($payment));
                }
            }
        }

        return PaymentResult::failure('Cashfree payment not completed.');
    }

    public function refund(string $transactionId, float $amount, string $currency, array $context = []): PaymentResult
    {
        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/orders/{$transactionId}/refunds", [
                'refund_amount' => $amount,
                'refund_id' => uniqid('rfnd_'),
                'refund_note' => 'Refund requested',
            ]);

        $data = $response->json();

        if ($response->successful() && !empty($data['refund_id'])) {
            return PaymentResult::success($data['refund_id'], json_encode($data));
        }

        return PaymentResult::failure($data['message'] ?? 'Cashfree refund failed.');
    }

    public function createWebhook(): array
    {
        return [
            'status' => 'not_configurable_via_api',
            'note' => 'Configure the webhook URL in the Cashfree dashboard/webhook API.',
            'url' => route('payment.webhook', 'cashfree'),
        ];
    }

    public function verifyWebhook(Request $request): bool
    {
        $timestamp = $request->header('x-webhook-timestamp');
        $signature = $request->header('x-webhook-signature');

        if (!$timestamp || !$signature) {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $timestamp . $request->getContent(), (string) $this->cfg('webhook_secret'), true));

        return hash_equals($expected, $signature);
    }

    protected static function meta(): array
    {
        return [
            'currencies' => ['INR'],
            'merchant_countries' => ['IN'],
            'customer_countries' => ['IN', 'US', 'GB', 'AE', 'SG', 'AU', 'CA', 'DE', 'FR', 'NP', 'BD', 'LK', 'SA', 'KW', 'QA'],
            'payment_methods' => ['card', 'upi', 'netbanking', 'wallet'],
        ];
    }
}
