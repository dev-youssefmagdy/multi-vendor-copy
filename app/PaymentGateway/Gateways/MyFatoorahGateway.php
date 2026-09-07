<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

/**
 * MyFatoorah Gateway
 * Composer: composer require baselrabia/myfatoorah  (or basel/myfatoorah)
 *   OR use the direct API below (no SDK dependency).
 * API Docs: https://docs.myfatoorah.com/docs/
 */
class MyFatoorahGateway extends AbstractPaymentGateway
{
    private string $baseUrl;

    public function getKey(): string
    {
        return 'myfatoorah';
    }

    protected function boot(): void
    {
        $this->requireConfig(['token', 'currency']);
        $this->baseUrl = $this->isSandbox()
            ? 'https://apitest.myfatoorah.com'
            : 'https://api.myfatoorah.com';
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $payload = [
            'NotificationOption'   => 'LNK',
            'InvoiceValue'         => $charge->amount,
            'DisplayCurrencyIso'   => $charge->currency,
            'CallBackUrl'          => $charge->successUrl,
            'ErrorUrl'             => $charge->cancelUrl,
            'CustomerName'         => $charge->name  ?? 'Customer',
            'CustomerEmail'        => $charge->email ?? 'noreply@example.com',
            'MobileCountryCode'    => '+965',
            'CustomerMobile'       => $charge->phone ?? '00000000',
            'Language'             => 'en',
            'CustomerReference'    => $charge->orderId ?? uniqid('mf_'),
            'InvoiceItems'         => [[
                'ItemName'  => $charge->description,
                'Quantity'  => 1,
                'UnitPrice' => $charge->amount,
            ]],
        ];

        $response = Http::withToken($this->cfg('token'))
            ->post("{$this->baseUrl}/v2/SendPayment", $payload);

        $data = $response->json();

        if ($data['IsSuccess'] ?? false) {
            $invoiceId  = $data['Data']['InvoiceId'] ?? null;
            $invoiceUrl = $data['Data']['InvoiceURL'] ?? null;
            Session::put('myfatoorah_invoice_id', $invoiceId);
            return PaymentResult::redirect($invoiceUrl);
        }

        return PaymentResult::failure($data['Message'] ?? 'MyFatoorah payment request failed.');
    }

    public function verify(Request $request): PaymentResult
    {
        $paymentId = $request->input('paymentId') ?? $request->input('InvoiceId')
            ?? Session::get('myfatoorah_invoice_id');

        if (!$paymentId) {
            return PaymentResult::failure('Missing MyFatoorah paymentId in callback.');
        }

        $response = Http::withToken($this->cfg('token'))
            ->post("{$this->baseUrl}/v2/GetPaymentStatus", [
                'Key'     => $paymentId,
                'KeyType' => 'InvoiceId',
            ]);

        $data = $response->json();

        if (($data['IsSuccess'] ?? false) && ($data['Data']['InvoiceStatus'] ?? '') === 'Paid') {
            Session::forget('myfatoorah_invoice_id');
            return PaymentResult::success((string) $paymentId, json_encode($data['Data']));
        }

        return PaymentResult::failure($data['Message'] ?? 'MyFatoorah verification failed.');
    }
}
