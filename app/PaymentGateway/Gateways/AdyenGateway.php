<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

/**
 * Adyen Gateway
 * Composer: No Composer SDK required — uses direct REST API calls.
 * API Docs: https://docs.adyen.com/api-explorer/
 */
class AdyenGateway extends AbstractPaymentGateway
{
    private string $baseUrl;

    public function getKey(): string
    {
        return 'adyen';
    }

    protected function boot(): void
    {
        $this->requireConfig(['api_key', 'merchant_account']);
        // Live traffic requires a per-merchant URL prefix in production (e.g. https://{prefix}-checkout-live.adyenpayments.com/checkout/v71).
        $this->baseUrl = $this->isSandbox()
            ? 'https://checkout-test.adyen.com/v71'
            : 'https://checkout-live.adyen.com/v71';
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $response = Http::withHeaders(['x-API-key' => $this->cfg('api_key')])
            ->post("{$this->baseUrl}/payments", [
                'amount' => [
                    'value' => (int) round($charge->amount * 100),
                    'currency' => $charge->currency,
                ],
                'reference' => $charge->orderId ?? uniqid('adyen_'),
                'paymentMethod' => request('paymentMethodPayload') ?? [],
                'returnUrl' => $charge->successUrl,
                'merchantAccount' => $this->cfg('merchant_account'),
            ]);

        $data = $response->json();

        if (($data['resultCode'] ?? null) === 'Authorised') {
            return PaymentResult::success($data['pspReference'], json_encode($data));
        }

        if (isset($data['action']['url'])) {
            Session::put('adyen_payment_data', $data['action']['paymentData'] ?? null);
            return PaymentResult::redirect($data['action']['url']);
        }

        return PaymentResult::failure($data['refusalReason'] ?? 'Adyen payment failed.');
    }

    public function verify(Request $request): PaymentResult
    {
        $payload = array_filter([
            'details' => array_filter([
                'redirectResult' => $request->input('redirectResult'),
                'payload' => $request->input('payload'),
            ]),
        ]);

        $response = Http::withHeaders(['x-API-key' => $this->cfg('api_key')])
            ->post("{$this->baseUrl}/payments/details", $payload);

        $data = $response->json();

        if (($data['resultCode'] ?? null) === 'Authorised') {
            Session::forget('adyen_payment_data');
            return PaymentResult::success($data['pspReference'], json_encode($data));
        }

        return PaymentResult::failure('Adyen payment not completed.');
    }

    public function refund(string $transactionId, float $amount, string $currency, array $context = []): PaymentResult
    {
        $response = Http::withHeaders(['x-API-key' => $this->cfg('api_key')])
            ->post("{$this->baseUrl}/payments/{$transactionId}/refunds", [
                'amount' => [
                    'value' => (int) round($amount * 100),
                    'currency' => $currency,
                ],
                'merchantAccount' => $this->cfg('merchant_account'),
            ]);

        $data = $response->json();

        if ($response->successful() && !empty($data['pspReference'])) {
            return PaymentResult::success($data['pspReference'], json_encode($data));
        }

        return PaymentResult::failure($data['message'] ?? 'Adyen refund failed.');
    }

    public function createWebhook(): array
    {
        return [
            'status' => 'not_configurable_via_api',
            'note' => 'Configure the webhook endpoint in the Adyen Customer Area.',
            'url' => route('payment.webhook', 'adyen'),
        ];
    }

    public function verifyWebhook(Request $request): bool
    {
        $items = $request->input('notificationItems');

        if (empty($items) || !is_array($items)) {
            return false;
        }

        $hmacKey = $this->cfg('webhook_hmac_key');

        if (!$hmacKey) {
            return true;
        }

        foreach ($items as $item) {
            $signature = $item['NotificationRequestItem']['additionalData']['hmacSignature'] ?? null;
            if (!$signature) {
                return false;
            }
            $expected = base64_encode(hash_hmac('sha256', json_encode($item), base64_decode($hmacKey), true));
            if (!hash_equals($expected, $signature)) {
                return false;
            }
        }

        return true;
    }

    protected static function meta(): array
    {
        return [
            'currencies' => ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'SGD', 'HKD', 'AED', 'SAR', 'CHF', 'SEK', 'NOK', 'DKK', 'PLN'],
            'merchant_countries' => ['US', 'GB', 'IE', 'FR', 'DE', 'NL', 'ES', 'IT', 'SE', 'SG', 'AU', 'HK', 'JP', 'BR', 'AE'],
            'customer_countries' => ['US', 'GB', 'IE', 'FR', 'DE', 'NL', 'ES', 'IT', 'SE', 'SG', 'AU', 'HK', 'JP', 'BR', 'AE', 'CA', 'CH', 'NO', 'DK', 'PL'],
            'payment_methods' => ['card', 'apple_pay', 'google_pay', 'wallets', 'bnpl'],
        ];
    }
}
