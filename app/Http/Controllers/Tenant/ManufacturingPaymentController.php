<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\ManufacturingPaymentRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ManufacturingPaymentRequest;
use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\Exceptions\PaymentException;
use App\PaymentGateway\PaymentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ManufacturingPaymentController
 *
 * Handles the payment lifecycle for manufacturing request payment requests:
 *   1. charge()  — initiate a gateway payment for a pending payment request
 *   2. success() — verify callback, mark as paid, redirect to detail page
 *   3. cancel()  — forget pending context, redirect back
 */
class ManufacturingPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentManager $manager,
    ) {
    }

    public function charge(Request $request, string $gateway, int $paymentRequestId): RedirectResponse|View
    {
        /** @var \App\Models\Tenant\AdminUser $admin */
        $admin = auth('tenant')->user();

        $paymentRequest = ManufacturingPaymentRequest::where('id', $paymentRequestId)
            ->where('tenant_id', tenant('id'))
            ->where('status', ManufacturingPaymentRequestStatus::Pending->value)
            ->firstOrFail();

        $manufacturingRequest = $paymentRequest->manufacturingRequest;
        $routeId = $manufacturingRequest->id;

        $charge = PaymentCharge::fromArray([
            'amount' => (float) $paymentRequest->amount,
            'currency' => strtoupper($paymentRequest->currency ?: 'USD'),
            'description' => "Manufacturing Request #{$routeId}: {$paymentRequest->label}",
            'order_id' => "mfg-pay-{$paymentRequest->id}",
            'email' => $admin->email ?? 'tenant@example.com',
            'success_url' => route('tenant.manufacturing-payment.success', $gateway)
                . '?payment_request_id=' . $paymentRequest->id,
            'cancel_url' => route('tenant.manufacturing-payment.cancel', $gateway)
                . '?manufacturing_request_id=' . $routeId,
        ]);

        session([
            'mf_pending_payment' => [
                'payment_request_id' => $paymentRequest->id,
                'manufacturing_request_id' => $routeId,
                'gateway' => $gateway,
            ],
        ]);

        try {
            $result = $this->manager->gateway($gateway)->charge($charge);
        } catch (PaymentException $e) {
            return redirect()->route('tenant.manufacturing.show', $routeId)
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

        return redirect()->route('tenant.manufacturing.show', $routeId)
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

        $pending = session('mf_pending_payment');
        $paymentRequestId = $request->integer('payment_request_id')
            ?: ($pending['payment_request_id'] ?? null);

        if (!$paymentRequestId) {
            return $this->redirectOnError($request, 'Payment reference lost. Please contact support.', $gateway);
        }

        $paymentRequest = ManufacturingPaymentRequest::where('id', $paymentRequestId)
            ->where('tenant_id', tenant('id'))
            ->first();

        if (!$paymentRequest) {
            return $this->redirectOnError($request, 'Payment request no longer exists.', $gateway);
        }

        return $this->handleSuccess($paymentRequest, $gateway, $result->transactionId);
    }

    public function cancel(Request $request, string $gateway): RedirectResponse
    {
        $pending = session('mf_pending_payment');
        $requestId = $request->integer('manufacturing_request_id')
            ?: ($pending['manufacturing_request_id'] ?? null);

        session()->forget('mf_pending_payment');

        if ($requestId) {
            return redirect()->route('tenant.manufacturing.show', $requestId)
                ->with('mf_payment_error', 'Payment was cancelled.');
        }

        return redirect()->route('tenant.manufacturing.index')
            ->with('mf_payment_error', 'Payment was cancelled.');
    }

    private function handleSuccess(
        ManufacturingPaymentRequest $paymentRequest,
        string $gateway,
        ?string $transactionId,
    ): RedirectResponse {
        session()->forget('mf_pending_payment');

        // Idempotent: only mark paid if still pending
        if ($paymentRequest->status === ManufacturingPaymentRequestStatus::Pending) {
            $paymentRequest->update([
                'status' => ManufacturingPaymentRequestStatus::Paid->value,
                'gateway_code' => $gateway,
                'transaction_id' => $transactionId,
                'paid_at' => now(),
            ]);
        }

        $manufacturingRequestId = $paymentRequest->manufacturing_request_id;

        return redirect()->route('tenant.manufacturing.show', $manufacturingRequestId)
            ->with('mf_payment_success', 'Payment successful! Your payment has been recorded.');
    }

    private function redirectOnError(Request $request, string $message, string $gateway): RedirectResponse
    {
        $pending = session('mf_pending_payment');
        $requestId = $pending['manufacturing_request_id']
            ?? $request->integer('manufacturing_request_id')
            ?: null;

        session()->forget('mf_pending_payment');

        if ($requestId) {
            return redirect()->route('tenant.manufacturing.show', $requestId)
                ->withErrors(['payment' => $message]);
        }

        return redirect()->route('tenant.manufacturing.index')
            ->withErrors(['payment' => $message]);
    }
}
