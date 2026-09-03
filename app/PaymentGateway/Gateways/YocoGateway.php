<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

/**
 * Yoco Gateway (South Africa)
 * No Composer SDK required — uses Yoco Payments REST API.
 * API Docs: https://developer.yoco.com/online/resources/integration-faqs
 */
class YocoGateway extends AbstractPaymentGateway
{
    private const CHECKOUT_URL = 'https://payments.yoco.com/api/checkouts';
    private const VERIFY_URL   = 'https://payments.yoco.com/api/checkouts/%s';

    public function getKey(): string
    {
        return 'yoco';
    }

    protected function boot(): void
    {
        $this->requireConfig(['secret_key']);
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $response = Http::withToken($this->cfg('secret_key'))
            ->post(self::CHECKOUT_URL, [
                'amount'     => (int) round($charge->amount * 100),  // cents
                'currency'   => $charge->currency ?: 'ZAR',
                'successUrl' => $charge->successUrl,
                'cancelUrl'  => $charge->cancelUrl,
                'metadata'   => array_merge(['order_id' => $charge->orderId], $charge->metadata),
            ]);

        $data = $response->json();

        if (!empty($data['redirectUrl'])) {
            Session::put('yoco_checkout_id', $data['id']);
            return PaymentResult::redirect($data['redirectUrl']);
        }

        return PaymentResult::failure($data['displayMessage'] ?? 'Yoco checkout creation failed.');
    }

    public function verify(Request $request): PaymentResult
    {
        $checkoutId = Session::get('yoco_checkout_id') ?? $request->input('id');

        if (!$checkoutId) {
            return PaymentResult::failure('Missing Yoco checkout ID in callback.');
        }

        $response = Http::withToken($this->cfg('secret_key'))
            ->get(sprintf(self::VERIFY_URL, $checkoutId));

        $data = $response->json();

        if (($data['status'] ?? '') === 'succeeded') {
            Session::forget('yoco_checkout_id');
            return PaymentResult::success($checkoutId, json_encode($data));
        }

        return PaymentResult::failure('Yoco checkout status: ' . ($data['status'] ?? 'unknown'));
    }
}
