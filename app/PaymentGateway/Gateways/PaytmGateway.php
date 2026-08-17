<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * Paytm Gateway
 * No official Composer SDK — uses Paytm's PHP checksum library (bundled).
 *
 * Copy Paytm's official checksum helper into:
 *   app/PaymentGateway/Helpers/PaytmChecksum.php
 * or use: composer require anandsiddharth/laravel-paytm-wallet
 *
 * API Docs: https://developer.paytm.com/docs/
 */
class PaytmGateway extends AbstractPaymentGateway
{
    public function getKey(): string
    {
        return 'paytm';
    }

    protected function boot(): void
    {
        $this->requireConfig(['merchant_id', 'merchant_key', 'website', 'industry_type', 'channel_id']);
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $txnToken = uniqid('paytm_');
        Session::put('paytm_order_id', $txnToken);

        $paramList = [
            'MID'               => $this->cfg('merchant_id'),
            'ORDER_ID'          => $txnToken,
            'CUST_ID'           => $txnToken,
            'INDUSTRY_TYPE_ID'  => $this->cfg('industry_type'),
            'CHANNEL_ID'        => $this->cfg('channel_id'),
            'TXN_AMOUNT'        => (string) $charge->amount,
            'WEBSITE'           => $this->cfg('website'),
            'CALLBACK_URL'      => $charge->successUrl,
        ];

        // Paytm requires checksum from their official library.
        // If using anandsiddharth/laravel-paytm-wallet the lib handles this.
        // Below is the raw approach — provide your own checksum function.
        if (function_exists('getChecksumFromArray')) {
            $checkSum = getChecksumFromArray($paramList, $this->cfg('merchant_key'));
        } else {
            $checkSum = '';   // Replace with actual checksum logic
        }

        $txnUrl = $this->isSandbox()
            ? 'https://securegw-stage.paytm.in/theia/processTransaction'
            : 'https://securegw.paytm.in/theia/processTransaction';

        return PaymentResult::view('payments.paytm', compact('txnUrl', 'paramList', 'checkSum'));
    }

    public function verify(Request $request): PaymentResult
    {
        $txnId     = $request->input('TXNID');
        $status    = $request->input('STATUS');
        $respMsg   = $request->input('RESPMSG');
        $checksum  = $request->input('CHECKSUMHASH');
        $params    = $request->except('CHECKSUMHASH');

        if (!$checksum || !$this->verifyChecksum($params, $checksum)) {
            return PaymentResult::failure('Paytm checksum verification failed.');
        }

        if ($status === 'TXN_SUCCESS') {
            Session::forget('paytm_order_id');
            return PaymentResult::success((string) $txnId);
        }

        return PaymentResult::failure("Paytm transaction failed: {$respMsg}");
    }

    /**
     * Verify Paytm's CHECKSUMHASH using their documented AES-128-CBC scheme.
     * https://developer.paytm.com/docs/checksum/
     */
    private function verifyChecksum(array $params, string $checksum): bool
    {
        $key = (string) $this->cfg('merchant_key');
        $iv  = '@@@@&&&&####$$$$';

        $decrypted = openssl_decrypt(base64_decode($checksum), 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false || strlen($decrypted) <= 4) {
            return false;
        }

        $salt = substr($decrypted, -4);
        $hash = substr($decrypted, 0, -4);

        ksort($params);
        $string = implode('|', array_map('strval', $params)) . '|' . $salt;

        return hash_equals(hash('sha256', $string), $hash);
    }
}
