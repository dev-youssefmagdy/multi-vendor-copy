<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

/**
 * Checkout.com Gateway
 * Composer: No Composer SDK required — uses direct REST API calls.
 * API Docs: https://api-reference.checkout.com/
 */
class CheckoutComGateway extends AbstractPaymentGateway
{
    private string $baseUrl;

    public function getKey(): string
    {
        return 'checkout_com';
    }

    protected function boot(): void
    {
        $this->requireConfig(['secret_key', 'public_key']);
        $this->baseUrl = $this->isSandbox()
            ? 'https://api.sandbox.checkout.com'
            : 'https://api.checkout.com';
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $response = Http::withToken($this->cfg('secret_key'))
            ->post("{$this->baseUrl}/payment-links", [
                'amount' => (int) round($charge->amount * 100),
                'currency' => $charge->currency,
                'reference' => $charge->orderId ?? uniqid('cko_'),
                'description' => $charge->description,
                'success_url' => $charge->successUrl,
                'failure_url' => $charge->cancelUrl,
                'processing_channel_id' => $this->cfg('processing_channel_id'),
            ]);

        $data = $response->json();

        if ($response->successful() && !empty($data['_links']['redirect']['href'])) {
            Session::put('checkout_com_payment_id', $data['id'] ?? null);
            return PaymentResult::redirect($data['_links']['redirect']['href']);
        }

        return PaymentResult::failure($data['error_type'] ?? 'Checkout.com payment initiation failed.');
    }

    public function verify(Request $request): PaymentResult
    {
        $id = $request->input('cko-payment-id') ?? $request->input('id') ?? Session::get('checkout_com_payment_id');

        if (!$id) {
            return PaymentResult::failure('Missing Checkout.com payment reference.');
        }

        $response = Http::withToken($this->cfg('secret_key'))->get("{$this->baseUrl}/payments/{$id}");
        $data = $response->json();

        if ($response->successful() && (($data['approved'] ?? false) || in_array($data['status'] ?? '', ['Authorized', 'Captured'], true))) {
            Session::forget('checkout_com_payment_id');
            return PaymentResult::success($data['id'], json_encode($data));
        }

        return PaymentResult::failure('Checkout.com payment not completed.');
    }

    public function refund(string $transactionId, float $amount, string $currency, array $context = []): PaymentResult
    {
        $response = Http::withToken($this->cfg('secret_key'))
            ->post("{$this->baseUrl}/payments/{$transactionId}/refunds", [
                'amount' => (int) round($amount * 100),
                'reference' => $context['reference'] ?? uniqid('refund_'),
            ]);

        $data = $response->json();

        if ($response->successful() && !empty($data['action_id'])) {
            return PaymentResult::success($data['action_id'], json_encode($data));
        }

        return PaymentResult::failure($data['error_type'] ?? 'Checkout.com refund failed.');
    }

    public function createWebhook(): array
    {
        $response = Http::withToken($this->cfg('secret_key'))
            ->post("{$this->baseUrl}/webhooks", [
                'url' => route('payment.webhook', 'checkout_com'),
                'event_types' => ['payment_approved', 'payment_captured', 'payment_declined', 'payment_refunded'],
            ]);

        return $response->json() ?? ['url' => route('payment.webhook', 'checkout_com')];
    }

    public function verifyWebhook(Request $request): bool
    {
        $secret = $this->cfg('webhook_secret');
        $signature = $request->header('Cko-Signature');

        if (!$secret || !$signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    protected static function meta(): array
    {
        return [
            'currencies' => ['USD', 'EUR', 'GBP', 'AED', 'SAR', 'SGD', 'AUD', 'CAD', 'CHF', 'SEK', 'NOK', 'HKD'],
            'merchant_countries' => ['US', 'GB', 'IE', 'FR', 'DE', 'ES', 'IT', 'NL', 'AE', 'SG', 'AU', 'HK', 'SA'],
            'customer_countries' => ['US', 'GB', 'IE', 'FR', 'DE', 'ES', 'IT', 'NL', 'AE', 'SA', 'SG', 'AU', 'HK', 'JP', 'CA', 'SE', 'NO', 'CH', 'BE', 'PT'],
            'payment_methods' => ['card', 'apple_pay', 'google_pay', 'wallets'],
        ];
    }
}
