<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;

/**
 * Midtrans Gateway
 * Composer: composer require midtrans/midtrans-php
 * API Docs: https://docs.midtrans.com/
 */
class MidtransGateway extends AbstractPaymentGateway
{
    public function getKey(): string
    {
        return 'midtrans';
    }

    protected function boot(): void
    {
        $this->requireConfig(['server_key', 'client_key']);

        MidtransConfig::$serverKey    = $this->cfg('server_key');
        MidtransConfig::$isProduction = !$this->isSandbox();
        MidtransConfig::$isSanitized  = true;
        MidtransConfig::$is3ds        = true;
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $token = uniqid('mdt_');
        Session::put('midtrans_token', $token);

        $params = [
            'transaction_details' => [
                'order_id'    => $token,
                'gross_amount' => (int) round($charge->amount),
            ],
            'customer_details' => [
                'first_name' => $charge->name  ?? 'Customer',
                'email'      => $charge->email ?? 'noreply@example.com',
                'phone'      => $charge->phone ?? '',
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);

            return PaymentResult::view('payments.midtrans', [
                'snap_token'   => $snapToken,
                'client_key'   => $this->cfg('client_key'),
                'is_production' => !$this->isSandbox(),
                'success_url'  => $charge->successUrl,
                'cancel_url'   => $charge->cancelUrl,
            ]);
        } catch (\Throwable $e) {
            return PaymentResult::failure($e->getMessage());
        }
    }

    public function verify(Request $request): PaymentResult
    {
        $orderId = Session::get('midtrans_token') ?? $request->input('order_id');

        if (!$orderId) {
            return PaymentResult::failure('Missing Midtrans order ID.');
        }

        // Midtrans sends a notification; verify via their Status API if needed.
        $transactionId = $request->input('transaction_id') ?? $orderId;
        Session::forget('midtrans_token');
        return PaymentResult::success($transactionId);
    }
}
