<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenant\PaymentController as WebPaymentController;
use App\Models\Tenant\MobileCart;
use App\Models\Tenant\Order;
use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\PaymentManager;
use App\Services\Tenant\OrderLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

/**
 * Hosted-gateway payments for the app's in-app WebView. The app never marks anything paid:
 * the gateway callback is verified by the gateway driver (verify()), and the app polls
 * GET /orders/{uuid}/payment-status for the real state.
 */
class PaymentController extends Controller
{
    /** POST /orders/{uuid}/pay (auth) -> {payment_url} to open in the WebView. */
    public function pay(Request $request, string $uuid, PaymentManager $manager, OrderLifecycleService $lifecycle): JsonResponse
    {
        $order = Order::query()->where('uuid', $uuid)->where('customer_id', $request->user()->id)->first();
        if (!$order) {
            return $this->fail('Order not found.', 'not_found', 404);
        }
        if ($order->paid) {
            return $this->fail('This order is already paid.', 'already_paid', 422);
        }
        if ($order->status instanceof OrderStatus && $order->status->isTerminal()) {
            return $this->fail('This order has been cancelled and can no longer be paid.', 'order_closed', 422);
        }

        // The gateway is whatever the order was placed with; the client cannot switch it here.
        $gateway = (string) $order->payment_method;
        if ($gateway === '' || $gateway === 'cod') {
            return $this->fail('This order does not use online payment.', 'not_online_payment', 422);
        }

        $tenantId = (string) tenant('id');
        $charge = PaymentCharge::fromArray([
            'amount' => $order->grand_total,
            'currency' => 'USD', // payment is always USD (display-only currency conversion)
            'description' => "Order #{$order->uuid}",
            'order_id' => $order->uuid,
            'email' => $request->user()->email,
            'success_url' => route('mobile.payments.success', ['tenant' => $tenantId, 'gateway' => $gateway]),
            'cancel_url' => URL::signedRoute('mobile.payments.cancel', ['tenant' => $tenantId, 'gateway' => $gateway, 'order' => $order->uuid]),
        ]);

        $lifecycle->recordPaymentInitiated($order, $gateway);
        Cache::put('mobile_pay_pending:'.$order->uuid, $gateway, now()->addHours(2));

        try {
            $result = $manager->gateway($gateway)->charge($charge);
        } catch (\Throwable $e) {
            report($e);
            Cache::forget('mobile_pay_pending:'.$order->uuid);

            return $this->fail('This payment method is currently unavailable.', 'gateway_unavailable', 502);
        }

        if ($result->needsRedirect) {
            return response()->json(['success' => true, 'data' => ['payment_url' => $result->redirectUrl, 'status' => 'redirect']]);
        }
        if ($result->success) {
            // Synchronous gateway: confirmed by the driver itself.
            $this->confirm($order, $gateway, $result->transactionId, $result->rawResponse);

            return response()->json(['success' => true, 'data' => ['payment_url' => null, 'status' => 'paid']]);
        }
        if ($result->needsView) {
            return $this->fail('This gateway cannot be used inside the app yet.', 'gateway_not_supported', 422);
        }

        return $this->fail('Payment initiation failed. Please try again.', 'payment_failed', 422);
    }

    /** GET|POST /payments/{tenant}/{gateway}/success : gateway return URL (no bearer token). */
    public function success(Request $request, string $gateway, PaymentManager $manager): RedirectResponse
    {
        try {
            $result = $manager->gateway($gateway)->verify($request);
        } catch (\Throwable) {
            return $this->back('failed');
        }

        $uuid = $result->extra['order_id'] ?? null;
        if (!$result->success || !$uuid) {
            return $this->back('failed', $uuid);
        }

        $order = Order::query()->where('uuid', $uuid)->first();
        // Only orders we actually started a payment for, with the same gateway, can be confirmed.
        if (!$order || $order->payment_method !== $gateway || Cache::get('mobile_pay_pending:'.$uuid) !== $gateway) {
            return $this->back('failed', $uuid);
        }

        if (!$order->paid) {
            $this->confirm($order, $gateway, $result->transactionId, $result->rawResponse);
        }

        return $this->back('success', $uuid);
    }

    /** GET /payments/{tenant}/{gateway}/cancel?order=&signature= */
    public function cancel(Request $request, string $gateway, OrderLifecycleService $lifecycle): RedirectResponse
    {
        $uuid = (string) $request->query('order');
        if ($uuid !== '' && $request->hasValidSignature()) {
            $order = Order::query()->where('uuid', $uuid)->where('paid', false)->first();
            if ($order) {
                $lifecycle->recordPaymentCancelled($order, $gateway);
            }
            Cache::forget('mobile_pay_pending:'.$uuid);
        }

        return $this->back('cancelled', $uuid ?: null);
    }

    /** GET /orders/{uuid}/payment-status (auth) : the only source of truth for the app. */
    public function status(Request $request, string $uuid): JsonResponse
    {
        $order = Order::query()->where('uuid', $uuid)->where('customer_id', $request->user()->id)->first();
        if (!$order) {
            return $this->fail('Order not found.', 'not_found', 404);
        }

        return response()->json(['success' => true, 'data' => [
            'uuid' => $order->uuid,
            'paid' => (bool) $order->paid,
            'status' => $order->status?->value,
            'payment_method' => $order->payment_method,
        ]]);
    }

    private function confirm(Order $order, string $gateway, ?string $txn, mixed $raw): void
    {
        $web = app(WebPaymentController::class);
        $details = ['transaction_id' => $txn, 'gateway' => $gateway, 'raw' => $raw];
        $web->confirmPayment($order, $gateway, $details);

        if ($companionUuid = Cache::pull('mobile_companion:'.$order->uuid)) {
            $companion = Order::query()->where('uuid', $companionUuid)->where('paid', false)->first();
            if ($companion) {
                $web->confirmPayment($companion, $gateway, $details + ['note' => 'Paid as companion order in split checkout'], notifyAdmin: false);
            }
        }

        Cache::forget('mobile_pay_pending:'.$order->uuid);
        // Paid: empty the customer's server cart + coupon.
        MobileCart::query()->where('customer_id', $order->customer_id)->update(['items' => [], 'coupon_code' => null]);
    }

    private function back(string $status, ?string $uuid = null): RedirectResponse
    {
        $scheme = (string) config('mobile.deep_link_scheme', 'storeapp');

        return redirect()->away($scheme.'://payment/result?'.http_build_query(array_filter(['status' => $status, 'order' => $uuid])));
    }

    private function fail(string $message, string $code, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, 'code' => $code], $status);
    }
}
