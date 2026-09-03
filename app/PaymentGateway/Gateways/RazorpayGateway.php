<?php

namespace App\PaymentGateway\Gateways;

use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

/**
 * Razorpay Gateway
 * Composer: composer require razorpay/razorpay
 */
class RazorpayGateway extends AbstractPaymentGateway
{
    private Api $api;

    public function getKey(): string
    {
        return 'razorpay';
    }

    protected function boot(): void
    {
        $this->requireConfig(['key', 'secret']);
        $this->api = new Api($this->cfg('key'), $this->cfg('secret'));
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        // Razorpay creates an order server-side, then renders a JS checkout form.
        try {
            $order = $this->api->order->create([
                'receipt'          => $charge->orderId,
                'amount'           => (int) round($charge->amount * 100),
                'currency'         => $charge->currency,
                'payment_capture'  => 1,
            ]);

            Session::put('razorpay_order_id', $order['id']);

            $viewData = [
                'key'        => $this->cfg('key'),
                'amount'     => $charge->amount,
                'currency'   => $charge->currency,
                'name'       => $charge->name ?? '',
                'email'      => $charge->email ?? '',
                'phone'      => $charge->phone ?? '',
                'order_id'   => $order['id'],
                'description' => $charge->description,
                'notify_url' => $charge->successUrl,
            ];

            return PaymentResult::view('payments.razorpay', $viewData);
        } catch (\Throwable $e) {
            return PaymentResult::failure($e->getMessage());
        }
    }

    public function verify(Request $request): PaymentResult
    {
        $orderId       = Session::get('razorpay_order_id');
        $paymentId     = $request->input('razorpay_payment_id');
        $razorpayOrderId = $request->input('razorpay_order_id');
        $signature     = $request->input('razorpay_signature');

        if (!$paymentId || !$signature) {
            return PaymentResult::failure('Missing Razorpay payment ID or signature.');
        }

        try {
            $this->api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $razorpayOrderId ?? $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature'  => $signature,
            ]);

            Session::forget('razorpay_order_id');
            return PaymentResult::success($paymentId);
        } catch (SignatureVerificationError $e) {
            return PaymentResult::failure('Razorpay signature verification failed: ' . $e->getMessage());
        }
    }
}
