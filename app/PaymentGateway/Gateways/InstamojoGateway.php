<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

/**
 * Instamojo Gateway
 * No official Composer SDK needed — uses Instamojo REST API.
 * API Docs: https://instamojo.com/developers/
 *
 * Alternatively install the unofficial wrapper:
 *   composer require instamojo/instamojo-php (for legacy API)
 */
class InstamojoGateway extends AbstractPaymentGateway
{
    private string $baseUrl;

    public function getKey(): string
    {
        return 'instamojo';
    }

    protected function boot(): void
    {
        $this->requireConfig(['api_key', 'auth_token']);
        $this->baseUrl = $this->isSandbox()
            ? 'https://test.instamojo.com/api/1.1'
            : 'https://www.instamojo.com/api/1.1';
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key'    => $this->cfg('api_key'),
                'X-Auth-Token' => $this->cfg('auth_token'),
            ])->post("{$this->baseUrl}/payment-requests/", [
                'purpose'      => $charge->description,
                'amount'       => $charge->amount,
                'send_email'   => false,
                'email'        => $charge->email ?? '',
                'redirect_url' => $charge->successUrl,
                'allow_repeated_payments' => false,
            ]);

            $data = $response->json();

            if (!empty($data['payment_request']['longurl'])) {
                Session::put('instamojo_payment_id', $data['payment_request']['id']);
                return PaymentResult::redirect($data['payment_request']['longurl']);
            }

            return PaymentResult::failure($data['message'] ?? 'Instamojo request creation failed.');
        } catch (\Throwable $e) {
            return PaymentResult::failure($e->getMessage());
        }
    }

    public function verify(Request $request): PaymentResult
    {
        $paymentRequestId = $request->input('payment_request_id')
            ?? Session::get('instamojo_payment_id');
        $paymentId        = $request->input('payment_id');

        if (!$paymentId || !$paymentRequestId) {
            return PaymentResult::failure('Missing Instamojo payment_request_id or payment_id.');
        }

        try {
            $response = Http::withHeaders([
                'X-Api-Key'    => $this->cfg('api_key'),
                'X-Auth-Token' => $this->cfg('auth_token'),
            ])->get("{$this->baseUrl}/payment-requests/{$paymentRequestId}/{$paymentId}/");

            $data = $response->json();

            if (($data['payment']['status'] ?? '') === 'Credit') {
                Session::forget('instamojo_payment_id');
                return PaymentResult::success($paymentId, json_encode($data['payment']));
            }

            return PaymentResult::failure('Instamojo payment status: ' . ($data['payment']['status'] ?? 'unknown'));
        } catch (\Throwable $e) {
            return PaymentResult::failure($e->getMessage());
        }
    }
}
