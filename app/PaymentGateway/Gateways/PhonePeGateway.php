<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

/**
 * PhonePe Gateway
 * No Composer SDK — uses PhonePe PG REST API with SHA256 signature.
 * API Docs: https://developer.phonepe.com/v1/docs/pay-api/
 */
class PhonePeGateway extends AbstractPaymentGateway
{
    public function getKey(): string
    {
        return 'phonepe';
    }

    protected function boot(): void
    {
        $this->requireConfig(['merchant_id', 'salt_key', 'salt_index']);
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $transactionId = uniqid('php_');
        Session::put('phonepe_transaction_id', $transactionId);

        $payload = [
            'merchantId'            => $this->cfg('merchant_id'),
            'merchantTransactionId' => $transactionId,
            'merchantUserId'        => 'MUID' . rand(100, 999),
            'amount'                => (int) round($charge->amount * 100),   // paise
            'redirectUrl'           => $charge->successUrl,
            'redirectMode'          => 'POST',
            'callbackUrl'           => $charge->successUrl,
            'mobileNumber'          => $charge->phone ?? '',
            'paymentInstrument'     => ['type' => 'PAY_PAGE'],
        ];

        $encode     = base64_encode(json_encode($payload));
        $saltKey    = $this->cfg('salt_key');
        $saltIndex  = $this->cfg('salt_index');
        $checksum   = hash('sha256', $encode . '/pg/v1/pay' . $saltKey) . '###' . $saltIndex;

        $apiUrl = $this->isSandbox()
            ? 'https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay'
            : 'https://api.phonepe.com/apis/pg/v1/pay';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-VERIFY'     => $checksum,
        ])->post($apiUrl, ['request' => $encode]);

        $data = $response->json();
        $redirectUrl = $data['data']['instrumentResponse']['redirectInfo']['url'] ?? null;

        if ($redirectUrl) {
            return PaymentResult::redirect($redirectUrl);
        }

        return PaymentResult::failure($data['message'] ?? 'PhonePe redirect URL not returned.');
    }

    public function verify(Request $request): PaymentResult
    {
        $transactionId = Session::get('phonepe_transaction_id')
            ?? $request->input('transactionId');

        if (!$transactionId) {
            return PaymentResult::failure('Missing PhonePe transactionId in callback.');
        }

        $saltKey   = $this->cfg('salt_key');
        $saltIndex = $this->cfg('salt_index');
        $statusPath = "/pg/v1/status/{$this->cfg('merchant_id')}/{$transactionId}";
        $checksum   = hash('sha256', $statusPath . $saltKey) . '###' . $saltIndex;

        $apiUrl = $this->isSandbox()
            ? "https://api-preprod.phonepe.com/apis/pg-sandbox{$statusPath}"
            : "https://api.phonepe.com/apis/pg{$statusPath}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-VERIFY'     => $checksum,
            'X-MERCHANT-ID' => $this->cfg('merchant_id'),
        ])->get($apiUrl);

        $data = $response->json();

        if (($data['success'] ?? false) && ($data['data']['state'] ?? '') === 'COMPLETED') {
            Session::forget('phonepe_transaction_id');
            return PaymentResult::success($transactionId, json_encode($data['data']));
        }

        return PaymentResult::failure($data['message'] ?? 'PhonePe verification failed.');
    }
}
