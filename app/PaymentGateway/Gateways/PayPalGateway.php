<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

/**
 * PayPal REST Gateway
 * Uses the REST API directly because paypal/rest-api-sdk-php is not PHP 8 safe.
 */
class PayPalGateway extends AbstractPaymentGateway
{
    public function getKey(): string
    {
        return 'paypal';
    }

    protected function boot(): void
    {
        $this->requireConfig(['client_id', 'client_secret']);
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        try {
            $response = $this->paypalRequest('post', '/v1/payments/payment', [
                'intent' => 'sale',
                'payer' => [
                    'payment_method' => 'paypal',
                ],
                'redirect_urls' => [
                    'return_url' => $charge->successUrl,
                    'cancel_url' => $charge->cancelUrl,
                ],
                'transactions' => [
                    [
                        'amount' => [
                            'currency' => strtoupper($charge->currency),
                            'total' => $this->formatAmount($charge->amount),
                        ],
                        'description' => $charge->description . ' via PayPal',
                        'item_list' => [
                            'items' => [
                                [
                                    'name' => str($charge->description)->limit(127, ''),
                                    'currency' => strtoupper($charge->currency),
                                    'quantity' => '1',
                                    'price' => $this->formatAmount($charge->amount),
                                ]
                            ],
                        ],
                    ]
                ],
            ]);
        } catch (\Throwable $e) {
            return PaymentResult::failure($e->getMessage());
        }

        $paymentId = (string) $response->json('id', '');
        $approvalLink = collect($response->json('links', []))
            ->firstWhere('rel', 'approval_url');
        $approvalUrl = is_array($approvalLink) ? ($approvalLink['href'] ?? null) : null;

        if ($paymentId !== '' && $approvalUrl) {
            Session::put('paypal_payment', [
                'payment_id' => $paymentId,
                'order_id' => $charge->orderId,
            ]);

            return PaymentResult::redirect($approvalUrl);
        }

        return PaymentResult::failure('Could not obtain PayPal approval URL.');
    }

    public function verify(Request $request): PaymentResult
    {
        $paypalSession = (array) Session::get('paypal_payment', []);
        $paymentId = (string) ($request->input('paymentId') ?: ($paypalSession['payment_id'] ?? ''));
        $payerId = (string) $request->input('PayerID', '');
        $orderId = (string) ($paypalSession['order_id'] ?? '');

        if ($paymentId === '' || $payerId === '') {
            return PaymentResult::failure('Invalid PayPal callback - missing payment ID or payer ID.');
        }

        try {
            $result = $this->paypalRequest('post', "/v1/payments/payment/{$paymentId}/execute", [
                'payer_id' => $payerId,
            ]);

            if ($result->json('state') === 'approved') {
                Session::forget('paypal_payment');

                return PaymentResult::success(
                    (string) $result->json('id', $paymentId),
                    $result->body(),
                    $orderId !== '' ? ['order_id' => $orderId] : [],
                );
            }

            return PaymentResult::failure('PayPal payment not approved. State: ' . $result->json('state', 'unknown'));
        } catch (\Throwable $e) {
            return PaymentResult::failure($e->getMessage());
        }
    }

    public function refund(string $transactionId, float $amount, string $currency, array $context = []): PaymentResult
    {
        try {
            $result = $this->paypalRequest('post', "/v2/payments/captures/{$transactionId}/refund", [
                'amount' => [
                    'value' => $this->formatAmount($amount),
                    'currency_code' => strtoupper($currency),
                ],
            ]);

            return PaymentResult::success((string) $result->json('id', $transactionId), $result->body());
        } catch (\Throwable $e) {
            return PaymentResult::failure($e->getMessage());
        }
    }

    public function createWebhook(): array
    {
        $url = route('payment.webhook', 'paypal');

        try {
            $result = $this->paypalRequest('post', '/v1/notifications/webhooks', [
                'url' => $url,
                'event_types' => [
                    ['name' => 'PAYMENT.CAPTURE.COMPLETED'],
                    ['name' => 'PAYMENT.CAPTURE.REFUNDED'],
                    ['name' => 'PAYMENT.CAPTURE.DENIED'],
                ],
            ]);

            return [
                'url' => $url,
                'events' => ['PAYMENT.CAPTURE.COMPLETED', 'PAYMENT.CAPTURE.REFUNDED', 'PAYMENT.CAPTURE.DENIED'],
                'id' => $result->json('id'),
            ];
        } catch (\Throwable $e) {
            return [
                'url' => $url,
                'events' => ['PAYMENT.CAPTURE.COMPLETED', 'PAYMENT.CAPTURE.REFUNDED', 'PAYMENT.CAPTURE.DENIED'],
            ];
        }
    }

    public function verifyWebhook(Request $request): bool
    {
        try {
            $result = $this->paypalRequest('post', '/v1/notifications/verify-webhook-signature', [
                'transmission_id' => $request->header('Paypal-Transmission-Id'),
                'transmission_time' => $request->header('Paypal-Transmission-Time'),
                'cert_url' => $request->header('Paypal-Cert-Url'),
                'auth_algo' => $request->header('Paypal-Auth-Algo'),
                'transmission_sig' => $request->header('Paypal-Transmission-Sig'),
                'webhook_id' => $this->cfg('webhook_id'),
                'webhook_event' => $request->json()->all(),
            ]);

            return $result->json('verification_status') === 'SUCCESS';
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected static function meta(): array
    {
        return [
            'currencies' => ['USD', 'EUR', 'GBP', 'AUD', 'CAD', 'SGD', 'AED'],
            'merchant_countries' => ['US', 'GB', 'CA', 'AU', 'DE', 'FR', 'IE', 'SG'],
            'customer_countries' => ['US', 'GB', 'CA', 'AU', 'DE', 'FR', 'ES', 'IT', 'NL', 'IE', 'SE', 'SG', 'JP', 'IN', 'BR', 'MX', 'ZA', 'AE'],
            'payment_methods' => ['card', 'wallet', 'paypal_balance'],
        ];
    }

    private function paypalRequest(string $method, string $uri, array $payload = [])
    {
        $response = Http::baseUrl($this->baseUrl())
            ->withToken($this->accessToken())
            ->acceptJson()
            ->timeout(30)
            ->send(strtoupper($method), $uri, [
                'json' => $payload,
            ]);

        if ($response->failed()) {
            $message = $response->json('message')
                ?? Arr::get($response->json(), 'details.0.issue')
                ?? $response->body()
                ?? 'PayPal request failed.';

            throw new \RuntimeException((string) $message);
        }

        return $response;
    }

    private function accessToken(): string
    {
        $response = Http::asForm()
            ->baseUrl($this->baseUrl())
            ->withBasicAuth($this->cfg('client_id'), $this->cfg('client_secret'))
            ->acceptJson()
            ->timeout(30)
            ->post('/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->failed() || blank($response->json('access_token'))) {
            $message = $response->json('error_description')
                ?? $response->json('error')
                ?? $response->body()
                ?? 'Unable to authenticate with PayPal.';

            throw new \RuntimeException((string) $message);
        }

        return (string) $response->json('access_token');
    }

    private function baseUrl(): string
    {
        return $this->isSandbox()
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
