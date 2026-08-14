<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

/**
 * PayTabs Gateway
 * No Composer SDK required — uses PayTabs REST API.
 * API Docs: https://site.paytabs.com/en/developer/
 *
 * Region URLs:
 *   UAE   → https://secure.paytabs.com
 *   SA    → https://secure.paytabs.sa
 *   Egypt → https://secure-egypt.paytabs.com
 *   Jordan→ https://secure-jordan.paytabs.com
 *   Iraq  → https://secure-iraq.paytabs.com
 *   Oman  → https://secure-oman.paytabs.com
 */
class PaytabsGateway extends AbstractPaymentGateway
{
    public function getKey(): string
    {
        return 'paytabs';
    }

    protected function boot(): void
    {
        $this->requireConfig(['profile_id', 'server_key', 'region_url', 'currency']);
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $endpoint = rtrim($this->cfg('region_url'), '/') . '/payment/request';

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->cfg('server_key'),
                'Content-Type'  => 'application/json',
            ])->post($endpoint, [
                'profile_id'       => $this->cfg('profile_id'),
                'tran_type'        => 'sale',
                'tran_class'       => 'ecom',
                'cart_id'          => $charge->orderId ?? uniqid('pt_'),
                'cart_description' => $charge->description,
                'cart_currency'    => $this->cfg('currency'),
                'cart_amount'      => round($charge->amount, 2),
                'return'           => $charge->successUrl,
                'callback'         => $charge->successUrl,
            ]);

            $data = $response->json();

            if (!empty($data['redirect_url'])) {
                return PaymentResult::redirect($data['redirect_url']);
            }

            return PaymentResult::failure($data['message'] ?? 'PayTabs redirect URL not returned.');
        } catch (\Throwable $e) {
            return PaymentResult::failure($e->getMessage());
        }
    }

    public function verify(Request $request): PaymentResult
    {
        $status  = $request->input('respStatus');
        $message = $request->input('respMessage');
        $tranRef = $request->input('tranRef');

        if ($status === 'A' && strtolower($message ?? '') === 'authorised') {
            return PaymentResult::success((string) $tranRef);
        }

        return PaymentResult::failure("PayTabs response: {$status} — {$message}");
    }

    public function refund(string $transactionId, float $amount, string $currency, array $context = []): PaymentResult
    {
        $endpoint = rtrim($this->cfg('region_url'), '/') . '/payment/request';

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->cfg('server_key'),
                'Content-Type'  => 'application/json',
            ])->post($endpoint, [
                'profile_id'    => $this->cfg('profile_id'),
                'tran_type'     => 'refund',
                'tran_class'    => 'ecom',
                'tran_ref'      => $transactionId,
                'cart_id'       => $context['order_id'] ?? uniqid('pt_rf_'),
                'cart_currency' => $currency,
                'cart_amount'   => round($amount, 2),
                'cart_description' => $context['reason'] ?? 'Refund',
            ]);

            $data = $response->json();

            if (($data['payment_result']['response_status'] ?? null) === 'A') {
                return PaymentResult::success((string) ($data['tran_ref'] ?? $transactionId), json_encode($data));
            }

            return PaymentResult::failure($data['message'] ?? 'PayTabs refund failed.');
        } catch (\Throwable $e) {
            return PaymentResult::failure($e->getMessage());
        }
    }

    public function createWebhook(): array
    {
        return [
            'url' => route('payment.webhook', 'paytabs'),
            'events' => ['payment.captured', 'payment.refunded'],
        ];
    }

    public function verifyWebhook(Request $request): bool
    {
        $tranRef = $request->input('tranRef') ?? $request->input('tran_ref');

        if (!$tranRef) {
            return false;
        }

        $endpoint = rtrim($this->cfg('region_url'), '/') . '/payment/query';

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->cfg('server_key'),
                'Content-Type'  => 'application/json',
            ])->post($endpoint, [
                'profile_id' => $this->cfg('profile_id'),
                'tran_ref'   => $tranRef,
            ]);

            $data = $response->json();

            return $response->successful()
                && ($data['tran_ref'] ?? null) === $tranRef
                && isset($data['payment_result']['response_status']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected static function meta(): array
    {
        return [
            'currencies' => ['SAR', 'AED', 'EGP', 'USD', 'KWD', 'BHD', 'OMR', 'QAR', 'JOD'],
            'merchant_countries' => ['SA', 'AE', 'EG', 'KW', 'BH', 'OM', 'QA', 'JO'],
            'customer_countries' => ['SA', 'AE', 'EG', 'KW', 'BH', 'OM', 'QA', 'JO', 'US', 'GB', 'DE', 'FR', 'IT', 'ES', 'NL', 'SE', 'IE', 'IN', 'PK', 'TR', 'CA', 'AU', 'SG', 'MY'],
            'payment_methods' => ['card', 'apple_pay', 'mada', 'stc_pay'],
        ];
    }
}
