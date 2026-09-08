<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * Perfect Money Gateway
 * No SDK — uses a simple HTML form POST to perfectmoney.com.
 * API Docs: https://perfectmoney.com/api/step1.asp
 */
class PerfectMoneyGateway extends AbstractPaymentGateway
{
    public function getKey(): string
    {
        return 'perfect_money';
    }

    protected function boot(): void
    {
        $this->requireConfig(['wallet_id', 'payee_name']);
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $paymentId = substr(uniqid(), 0, 8);
        Session::put('perfect_money_payment_id', $paymentId);
        Session::put('perfect_money_amount',     $charge->amount);

        $formData = [
            'PAYEE_ACCOUNT'       => $this->cfg('wallet_id'),
            'PAYEE_NAME'          => $this->cfg('payee_name'),
            'PAYMENT_ID'          => $paymentId,
            'PAYMENT_AMOUNT'      => $charge->amount,
            'PAYMENT_UNITS'       => $charge->currency,
            'STATUS_URL'          => $charge->successUrl,
            'PAYMENT_URL'         => $charge->successUrl,
            'PAYMENT_URL_METHOD'  => 'GET',
            'NOPAYMENT_URL'       => $charge->cancelUrl,
            'NOPAYMENT_URL_METHOD' => 'GET',
            'SUGGESTED_MEMO'      => $charge->email ?? '',
            'BAGGAGE_FIELDS'      => 'IDENT',
        ];

        return PaymentResult::view('payments.perfect-money', [
            'formData' => $formData,
            'postUrl'  => 'https://perfectmoney.com/api/step1.asp',
        ]);
    }

    public function verify(Request $request): PaymentResult
    {
        $expectedWallet = $this->cfg('wallet_id');
        $expectedId     = Session::get('perfect_money_payment_id');
        $expectedAmount = Session::get('perfect_money_amount');

        $payeeAccount   = $request->input('PAYEE_ACCOUNT');
        $paymentId      = $request->input('PAYMENT_ID');
        $paymentAmount  = $request->input('PAYMENT_AMOUNT');

        if (
            $payeeAccount === $expectedWallet &&
            $paymentId    === $expectedId     &&
            (float) $paymentAmount === round((float) $expectedAmount, 2)
        ) {
            Session::forget(['perfect_money_payment_id', 'perfect_money_amount']);
            return PaymentResult::success((string) $paymentId);
        }

        return PaymentResult::failure('Perfect Money payment verification failed — data mismatch.');
    }

    protected static function meta(): array
    {
        return [
            'currencies' => ['USD', 'EUR', 'GOLD'],
            'merchant_countries' => ['RU', 'UA', 'BY', 'KZ', 'UZ', 'AM', 'AZ'],
            'customer_countries' => ['RU', 'UA', 'BY', 'KZ', 'UZ', 'AM', 'AZ', 'US', 'DE', 'FR'],
            'payment_methods' => ['perfect_money_wallet', 'voucher'],
        ];
    }
}
