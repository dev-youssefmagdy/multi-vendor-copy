<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * Xendit Gateway
 * No Composer SDK required — uses Xendit REST API.
 * API Docs: https://developers.xendit.co/api-reference/
 */
class XenditGateway extends AbstractPaymentGateway
{
    private const INVOICE_URL = 'https://api.xendit.co/v2/invoices';

    public function getKey(): string
    {
        return 'xendit';
    }

    protected function boot(): void
    {
        $this->requireConfig(['secret_key']);
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $externalId = Str::random(12);

        $response = Http::withBasicAuth($this->cfg('secret_key'), '')
            ->post(self::INVOICE_URL, [
                'external_id'          => $externalId,
                'amount'               => $charge->amount,
                'currency'             => $charge->currency,
                'description'          => $charge->description,
                'success_redirect_url' => $charge->successUrl,
                'failure_redirect_url' => $charge->cancelUrl,
                'payer_email'          => $charge->email ?? 'noreply@example.com',
            ]);

        $data = $response->json();

        if (!empty($data['invoice_url'])) {
            Session::put('xendit_invoice_id', $data['id']);
            Session::put('xendit_secret_key', $this->cfg('secret_key'));
            return PaymentResult::redirect($data['invoice_url']);
        }

        return PaymentResult::failure($data['message'] ?? 'Xendit invoice creation failed.');
    }

    public function verify(Request $request): PaymentResult
    {
        $invoiceId = $request->input('id') ?? Session::get('xendit_invoice_id');

        if (!$invoiceId) {
            return PaymentResult::failure('Missing Xendit invoice ID in callback.');
        }

        $secretKey = Session::get('xendit_secret_key') ?? $this->cfg('secret_key');

        $response = Http::withBasicAuth($secretKey, '')
            ->get(self::INVOICE_URL . '/' . $invoiceId);

        $data = $response->json();

        if (($data['status'] ?? '') === 'PAID') {
            Session::forget(['xendit_invoice_id', 'xendit_secret_key']);
            return PaymentResult::success($invoiceId, json_encode($data));
        }

        return PaymentResult::failure('Xendit invoice status: ' . ($data['status'] ?? 'unknown'));
    }
}
