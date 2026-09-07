<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

/**
 * Flutterwave (Rave) Gateway
 * Composer: composer require kingflamez/laravelrave
 *   OR use direct HTTP calls (no SDK dependency — implemented here).
 * API Docs: https://developer.flutterwave.com/docs
 */
class FlutterwaveGateway extends AbstractPaymentGateway
{
    private const INITIATE_URL = 'https://api.flutterwave.com/v3/payments';
    private const VERIFY_URL   = 'https://api.flutterwave.com/v3/transactions/%s/verify';

    public function getKey(): string
    {
        return 'flutterwave';
    }

    protected function boot(): void
    {
        $this->requireConfig(['secret_key']);
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $txRef = $charge->orderId ?? uniqid('flw_');
        Session::put('flutterwave_tx_ref', $txRef);

        $payload = [
            'tx_ref'       => $txRef,
            'amount'       => $charge->amount,
            'currency'     => $charge->currency,
            'redirect_url' => $charge->successUrl,
            'customer'     => [
                'email'       => $charge->email  ?? 'noreply@example.com',
                'name'        => $charge->name   ?? 'Customer',
                'phonenumber' => $charge->phone  ?? '',
            ],
            'customizations' => [
                'title'       => $charge->description,
            ],
        ];

        $response = Http::withToken($this->cfg('secret_key'))
            ->post(self::INITIATE_URL, $payload);

        $data = $response->json();

        if (($data['status'] ?? '') === 'success' && !empty($data['data']['link'])) {
            return PaymentResult::redirect($data['data']['link']);
        }

        return PaymentResult::failure($data['message'] ?? 'Flutterwave initiation failed.');
    }

    public function verify(Request $request): PaymentResult
    {
        $txId = $request->input('transaction_id');

        if (!$txId) {
            return PaymentResult::failure('Missing Flutterwave transaction_id in callback.');
        }

        $response = Http::withToken($this->cfg('secret_key'))
            ->get(sprintf(self::VERIFY_URL, $txId));

        $data = $response->json();

        if (($data['status'] ?? '') === 'success' && ($data['data']['status'] ?? '') === 'successful') {
            Session::forget('flutterwave_tx_ref');
            return PaymentResult::success((string) $txId, json_encode($data['data']));
        }

        return PaymentResult::failure($data['message'] ?? 'Flutterwave verification failed.');
    }
}
