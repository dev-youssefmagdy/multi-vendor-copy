<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

/**
 * ToyyibPay Gateway
 * No Composer SDK — uses ToyyibPay REST API with cURL / HTTP.
 * API Docs: https://toyyibpay.com/apireference/
 */
class ToyyibpayGateway extends AbstractPaymentGateway
{
    public function getKey(): string
    {
        return 'toyyibpay';
    }

    protected function boot(): void
    {
        $this->requireConfig(['secret_key', 'category_code']);
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $ref      = uniqid('tpay_');
        $baseHost = $this->isSandbox()
            ? 'https://dev.toyyibpay.com/'
            : 'https://toyyibpay.com/';

        Session::put('toyyibpay_ref', $ref);

        $payload = [
            'userSecretKey'          => $this->cfg('secret_key'),
            'categoryCode'           => $this->cfg('category_code'),
            'billName'               => mb_substr($charge->description, 0, 30),
            'billDescription'        => $charge->description,
            'billPriceSetting'       => 1,
            'billPayorInfo'          => 1,
            'billAmount'             => (int) round($charge->amount * 100),  // sen
            'billReturnUrl'          => $charge->successUrl,
            'billExternalReferenceNo' => $ref,
            'billTo'                 => $charge->name  ?? 'Customer',
            'billEmail'              => $charge->email ?? 'noreply@example.com',
            'billPhone'              => $charge->phone ?? '',
        ];

        $response = Http::asForm()->post($baseHost . 'index.php/api/createBill', $payload);
        $data     = $response->json();

        if (!empty($data[0]['BillCode'])) {
            $billCode = $data[0]['BillCode'];
            $checkoutUrl = $baseHost . $billCode;
            return PaymentResult::redirect($checkoutUrl);
        }

        return PaymentResult::failure($data[0]['msg'] ?? 'ToyyibPay bill creation failed.');
    }

    public function verify(Request $request): PaymentResult
    {
        $statusId  = $request->input('status_id');
        $billCode  = $request->input('billcode');
        $orderRef  = $request->input('order_id_8') ?? Session::get('toyyibpay_ref');

        // status_id: 1 = success, 2 = pending, 3 = fail
        if ((string) $statusId === '1') {
            Session::forget('toyyibpay_ref');
            return PaymentResult::success((string) $billCode);
        }

        return PaymentResult::failure('ToyyibPay payment status_id: ' . $statusId);
    }
}
