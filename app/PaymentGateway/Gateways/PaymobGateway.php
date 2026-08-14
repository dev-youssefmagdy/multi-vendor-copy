<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Paymob Gateway
 * Composer: No Composer SDK required — uses direct REST API calls.
 * API Docs: https://docs.paymob.com/
 */
class PaymobGateway extends AbstractPaymentGateway
{
    private const BASE_URL = 'https://accept.paymob.com/api';

    public function getKey(): string
    {
        return 'paymob';
    }

    protected function boot(): void
    {
        $this->requireConfig(['api_key', 'integration_id', 'iframe_id']);
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        try {
            $authResponse = Http::post(self::BASE_URL . '/auth/tokens', [
                'api_key' => $this->cfg('api_key'),
            ]);
            if (!$authResponse->successful() || empty($authResponse->json('token'))) {
                return PaymentResult::failure('Paymob authentication failed.');
            }
            $token = $authResponse->json('token');

            $amountCents = (int) round($charge->amount * 100);

            $orderResponse = Http::post(self::BASE_URL . '/ecommerce/orders', [
                'auth_token' => $token,
                'delivery_needed' => false,
                'amount_cents' => $amountCents,
                'currency' => $charge->currency,
                'merchant_order_id' => $charge->orderId,
            ]);
            if (!$orderResponse->successful() || empty($orderResponse->json('id'))) {
                return PaymentResult::failure('Paymob order creation failed.');
            }
            $orderId = $orderResponse->json('id');

            $keyResponse = Http::post(self::BASE_URL . '/acceptance/payment_keys', [
                'auth_token' => $token,
                'amount_cents' => $amountCents,
                'expiration' => 3600,
                'order_id' => $orderId,
                'billing_data' => [
                    'first_name' => $charge->name ?? 'NA',
                    'last_name' => 'NA',
                    'email' => $charge->email ?? 'NA',
                    'phone_number' => $charge->phone ?? 'NA',
                    'city' => 'NA',
                    'country' => 'NA',
                    'street' => 'NA',
                    'building' => 'NA',
                    'floor' => 'NA',
                    'apartment' => 'NA',
                    'state' => 'NA',
                    'postal_code' => 'NA',
                ],
                'currency' => $charge->currency,
                'integration_id' => $this->cfg('integration_id'),
            ]);
            if (!$keyResponse->successful() || empty($keyResponse->json('token'))) {
                return PaymentResult::failure('Paymob payment key generation failed.');
            }
            $paymentKey = $keyResponse->json('token');

            return PaymentResult::redirect("https://accept.paymob.com/api/acceptance/iframes/{$this->cfg('iframe_id')}?payment_token={$paymentKey}");
        } catch (\Throwable $e) {
            return PaymentResult::failure($e->getMessage());
        }
    }

    private function computeHmac(array $data, string $secret): string
    {
        $fields = [
            'amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction',
            'id', 'integration_id', 'is_3d_secure', 'is_auction', 'is_capture', 'is_refunded',
            'is_standalone_payment', 'is_voided', 'order.id', 'owner', 'pending',
            'source_data.pan', 'source_data.sub_type', 'source_data.type', 'success',
        ];

        $concatenated = '';
        foreach ($fields as $field) {
            $concatenated .= data_get($data, $field, '');
        }

        return hash_hmac('sha512', $concatenated, $secret);
    }

    public function verify(Request $request): PaymentResult
    {
        $success = $request->input('success');
        $hmac = $request->input('hmac');
        $expected = $this->computeHmac($request->all(), (string) $this->cfg('hmac_secret'));

        if (!$hmac || !hash_equals($expected, $hmac)) {
            return PaymentResult::failure('Paymob HMAC verification failed.');
        }

        if ($success === 'true') {
            return PaymentResult::success((string) $request->input('id'), json_encode($request->all()));
        }

        return PaymentResult::failure('Paymob payment not completed.');
    }

    public function refund(string $transactionId, float $amount, string $currency, array $context = []): PaymentResult
    {
        $authResponse = Http::post(self::BASE_URL . '/auth/tokens', ['api_key' => $this->cfg('api_key')]);
        if (!$authResponse->successful()) {
            return PaymentResult::failure('Paymob authentication failed.');
        }
        $token = $authResponse->json('token');

        $response = Http::post(self::BASE_URL . '/acceptance/void_refund/refund', [
            'auth_token' => $token,
            'transaction_id' => $transactionId,
            'amount_cents' => (int) round($amount * 100),
        ]);

        $data = $response->json();

        if ($response->successful() && ($data['success'] ?? false)) {
            return PaymentResult::success((string) ($data['id'] ?? $transactionId), json_encode($data));
        }

        return PaymentResult::failure($data['message'] ?? 'Paymob refund failed.');
    }

    public function createWebhook(): array
    {
        return [
            'status' => 'not_configurable_via_api',
            'note' => 'Configure the transaction processed callback/HMAC in the Paymob integration settings.',
            'url' => route('payment.webhook', 'paymob'),
        ];
    }

    public function verifyWebhook(Request $request): bool
    {
        $hmac = $request->input('hmac');
        if (!$hmac) {
            return false;
        }
        $expected = $this->computeHmac($request->all(), (string) $this->cfg('hmac_secret'));

        return hash_equals($expected, $hmac);
    }

    protected static function meta(): array
    {
        return [
            'currencies' => ['EGP', 'USD'],
            'merchant_countries' => ['EG', 'SA', 'AE', 'PK', 'OM'],
            'customer_countries' => ['EG', 'SA', 'AE', 'PK', 'OM', 'JO', 'KW', 'BH', 'QA', 'US', 'GB', 'DE', 'FR', 'IN', 'TR'],
            'payment_methods' => ['card', 'mobile_wallet', 'kiosk'],
        ];
    }
}
