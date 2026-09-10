<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\BrandPaymentRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\BrandPaymentRequest;
use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\Exceptions\PaymentException;
use App\PaymentGateway\PaymentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * BrandRequestPaymentController
 *
 * Handles the payment lifecycle for brand request payment requests:
 *   1. charge()  — initiate a gateway payment for a pending payment request
 *   2. success() — verify callback, mark as paid, redirect to detail page
 *   3. cancel()  — forget pending context, redirect back
 */
class BrandRequestPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentManager $manager,
    ) {
    }

    public function charge(Request $request, string $gateway, int $paymentRequestId): RedirectResponse|View
    {
        /** @var \App\Models\Tenant\AdminUser $admin */
        $admin = auth('tenant')->user();

        $paymentRequest = BrandPaymentRequest::where('id', $paymentRequestId)
            ->where('tenant_id', tenant('id'))
            ->where('status', BrandPaymentRequestStatus::Pending->value)
            ->firstOrFail();

        $brandRequest = $paymentRequest->brandRequest;
        $routeId = $brandRequest->id;

        $charge = PaymentCharge::fromArray([
            'amount' => (float) $paymentRequest->amount,
            'currency' => strtoupper($paymentRequest->currency ?: 'USD'),
            'description' => "Brand Request #{$routeId}: {$paymentRequest->label}",
            'order_id' => "brand-pay-{$paymentRequest->id}",
            'email' => $admin->email ?? 'tenant@example.com',
            'success_url' => route('tenant.brand-request-payment.success', $gateway)
                . '?payment_request_id=' . $paymentRequest->id,
            'cancel_url' => route('tenant.brand-request-payment.cancel', $gateway)
                . '?brand_request_id=' . $routeId,
        ]);

        session([
            'br_pending_payment' => [
                'payment_request_id' => $paymentRequest->id,
                'brand_request_id' => $routeId,
                'gateway' => $gateway,
            ],
        ]);

        try {
            $result = $this->manager->gateway($gateway)->charge($charge);
        } catch (PaymentException $e) {
            return redirect()->route('tenant.brand-requests.show', $routeId)
                ->withErrors(['payment' => $e->getMessage()]);
        }

        if ($result->needsRedirect) {
            return redirect()->away($result->redirectUrl);
        }

        if ($result->needsView) {
            return view($result->viewName, $result->viewData);
        }

        if ($result->success) {
            return $this->handleSuccess(
                $paymentRequest,
                $gateway,
                $result->transactionId,
            );
        }

        return redirect()->route('tenant.brand-requests.show', $routeId)
            ->withErrors(['payment' => $result->errorMessage ?? 'Payment initiation failed. Please try again.']);
    }

    public function success(Request $request, string $gateway): RedirectResponse
    {
        try {
            $result = $this->manager->gateway($gateway)->verify($request);
        } catch (PaymentException $e) {
            return $this->redirectOnError($request, $e->getMessage(), $gateway);
        } catch (\Throwable) {
            return $this->redirectOnError($request, 'Payment verification failed. Please contact support.', $gateway);
        }

        if (!$result->success) {
            return $this->redirectOnError(
                $request,
                $result->errorMessage ?? 'Payment was not successful.',
                $gateway,
            );
        }

        $pending = session('br_pending_payment');
        $paymentRequestId = $request->integer('payment_request_id')
            ?: ($pending['payment_request_id'] ?? null);

        if (!$paymentRequestId) {
            return $this->redirectOnError($request, 'Payment reference lost. Please contact support.', $gateway);
        }

        $paymentRequest = BrandPaymentRequest::where('id', $paymentRequestId)
            ->where('tenant_id', tenant('id'))
            ->first();

        if (!$paymentRequest) {
            return $this->redirectOnError($request, 'Payment request no longer exists.', $gateway);
        }

        return $this->handleSuccess($paymentRequest, $gateway, $result->transactionId);
    }

    public function cancel(Request $request, string $gateway): RedirectResponse
    {
        $pending = session('br_pending_payment');
        $requestId = $request->integer('brand_request_id')
            ?: ($pending['brand_request_id'] ?? null);

        session()->forget('br_pending_payment');

        if ($requestId) {
            return redirect()->route('tenant.brand-requests.show', $requestId)
                ->with('br_payment_error', 'Payment was cancelled.');
        }

        return redirect()->route('tenant.brand-requests.index')
            ->with('br_payment_error', 'Payment was cancelled.');
    }

    private function handleSuccess(
        BrandPaymentRequest $paymentRequest,
        string $gateway,
        ?string $transactionId,
    ): RedirectResponse {
        session()->forget('br_pending_payment');

        // Idempotent: only mark paid if still pending
        if ($paymentRequest->status === BrandPaymentRequestStatus::Pending) {
            $paymentRequest->update([
                'status' => BrandPaymentRequestStatus::Paid->value,
                'gateway_code' => $gateway,
                'transaction_id' => $transactionId,
                'paid_at' => now(),
            ]);
        }

        $brandRequestId = $paymentRequest->brand_request_id;

        return redirect()->route('tenant.brand-requests.show', $brandRequestId)
            ->with('br_payment_success', 'Payment successful! Your payment has been recorded.');
    }

    private function redirectOnError(Request $request, string $message, string $gateway): RedirectResponse
    {
        $pending = session('br_pending_payment');
        $requestId = $pending['brand_request_id']
            ?? $request->integer('brand_request_id')
            ?: null;

        session()->forget('br_pending_payment');

        if ($requestId) {
            return redirect()->route('tenant.brand-requests.show', $requestId)
                ->withErrors(['payment' => $message]);
        }

        return redirect()->route('tenant.brand-requests.index')
            ->withErrors(['payment' => $message]);
    }
}
