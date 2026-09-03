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
}
