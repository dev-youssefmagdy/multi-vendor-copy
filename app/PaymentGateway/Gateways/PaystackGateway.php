<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

/**
 * Paystack Gateway
 * No SDK required — uses the Paystack REST API directly.
 * API Docs: https://paystack.com/docs/api/
 */
class PaystackGateway extends AbstractPaymentGateway
{
    private const INIT_URL   = 'https://api.paystack.co/transaction/initialize';
    private const VERIFY_URL = 'https://api.paystack.co/transaction/verify/%s';

    public function getKey(): string
    {
        return 'paystack';
    }

    protected function boot(): void
    {
        $this->requireConfig(['secret_key']);
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        if (empty($charge->email)) {
            return PaymentResult::failure('Paystack requires a customer email address.');
        }

        $response = Http::withToken($this->cfg('secret_key'))
            ->post(self::INIT_URL, [
                'email'        => $charge->email,
                'amount'       => (int) round($charge->amount * 100),   // Paystack uses kobo/pesewa
                'currency'     => $charge->currency,
                'callback_url' => $charge->successUrl,
                'reference'    => $charge->orderId,
                'metadata'     => $charge->metadata,
            ]);

        $data = $response->json();

        if ($data['status'] === true && !empty($data['data']['authorization_url'])) {
            Session::put('paystack_reference', $data['data']['reference']);
            return PaymentResult::redirect($data['data']['authorization_url']);
        }

        return PaymentResult::failure($data['message'] ?? 'Paystack initialisation failed.');
    }

    public function verify(Request $request): PaymentResult
    {
        $reference = $request->input('reference') ?? $request->input('trxref')
            ?? Session::get('paystack_reference');

        if (!$reference) {
            return PaymentResult::failure('Missing Paystack reference in callback.');
        }

        $response = Http::withToken($this->cfg('secret_key'))
            ->get(sprintf(self::VERIFY_URL, $reference));

        $data = $response->json();

        if ($data['status'] === true && ($data['data']['status'] ?? '') === 'success') {
            Session::forget('paystack_reference');
            return PaymentResult::success($reference, json_encode($data['data']));
        }

        return PaymentResult::failure($data['message'] ?? 'Paystack verification failed.');
    }

    protected static function meta(): array
    {
        return [
            'currencies' => ['NGN', 'GHS', 'ZAR', 'KES', 'USD'],
            'merchant_countries' => ['NG', 'GH', 'ZA', 'KE'],
            'customer_countries' => ['NG', 'GH', 'ZA', 'KE', 'US', 'GB'],
            'payment_methods' => ['card', 'bank_transfer', 'ussd', 'mobile_money'],
        ];
    }
}
