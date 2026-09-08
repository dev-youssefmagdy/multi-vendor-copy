<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;

/**
 * Midtrans Gateway
 * Composer: composer require midtrans/midtrans-php
 * API Docs: https://docs.midtrans.com/
 */
class MidtransGateway extends AbstractPaymentGateway
{
    public function getKey(): string
    {
        return 'midtrans';
    }

    protected function boot(): void
    {
        $this->requireConfig(['server_key', 'client_key']);

        MidtransConfig::$serverKey    = $this->cfg('server_key');
        MidtransConfig::$isProduction = !$this->isSandbox();
        MidtransConfig::$isSanitized  = true;
        MidtransConfig::$is3ds        = true;
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $token = uniqid('mdt_');
        Session::put('midtrans_token', $token);

        $params = [
            'transaction_details' => [
                'order_id'    => $token,
                'gross_amount' => (int) round($charge->amount),
            ],
            'customer_details' => [
                'first_name' => $charge->name  ?? 'Customer',
                'email'      => $charge->email ?? 'noreply@example.com',
                'phone'      => $charge->phone ?? '',
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);

            return PaymentResult::view('payments.midtrans', [
                'snap_token'   => $snapToken,
                'client_key'   => $this->cfg('client_key'),
                'is_production' => !$this->isSandbox(),
                'success_url'  => $charge->successUrl,
                'cancel_url'   => $charge->cancelUrl,
            ]);
        } catch (\Throwable $e) {
            return PaymentResult::failure($e->getMessage());
        }
    }

    public function verify(Request $request): PaymentResult
    {
        $orderId = Session::get('midtrans_token') ?? $request->input('order_id');

        if (!$orderId) {
            return PaymentResult::failure('Missing Midtrans order ID.');
        }

        // Midtrans sends a notification; verify via their Status API if needed.
        $transactionId = $request->input('transaction_id') ?? $orderId;
        Session::forget('midtrans_token');
        return PaymentResult::success($transactionId);
    }

    public function refund(string $transactionId, float $amount, string $currency, array $context = []): PaymentResult
    {
        $base = $this->isSandbox()
            ? 'https://api.sandbox.midtrans.com'
            : 'https://api.midtrans.com';

        try {
            $response = Http::withBasicAuth($this->cfg('server_key'), '')
                ->post("{$base}/v2/{$transactionId}/refund", [
                    'amount' => (int) round($amount),
                    'reason' => $context['reason'] ?? 'Refund',
                ]);

            $data = $response->json();

            if ($response->successful() && in_array($data['status_code'] ?? null, ['200', '201'])) {
                return PaymentResult::success($transactionId, json_encode($data));
            }

            return PaymentResult::failure($data['status_message'] ?? 'Midtrans refund failed.');
        } catch (\Throwable $e) {
            return PaymentResult::failure($e->getMessage());
        }
    }

    public function createWebhook(): array
    {
        return [
            'url' => route('payment.webhook', 'midtrans'),
            'events' => ['transaction_status'],
        ];
    }

    public function verifyWebhook(Request $request): bool
    {
        $orderId = $request->input('order_id');
        $statusCode = $request->input('status_code');
        $grossAmount = $request->input('gross_amount');
        $signatureKey = $request->input('signature_key');

        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
            return false;
        }

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $this->cfg('server_key'));

        return hash_equals($expected, $signatureKey);
    }

    protected static function meta(): array
    {
        return [
            'currencies' => ['IDR'],
            'merchant_countries' => ['ID'],
            'customer_countries' => ['ID', 'US', 'GB', 'AU', 'SG', 'MY', 'JP', 'IN', 'DE', 'FR', 'NL', 'AE', 'TH', 'PH', 'VN'],
            'payment_methods' => ['card', 'gopay', 'bank_transfer', 'qris'],
        ];
    }
}
