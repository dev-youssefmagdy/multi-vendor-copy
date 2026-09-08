<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use App\Models\Tenant\PaymentGateway as TenantPaymentGateway;
use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\DTOs\PaymentResult;
use App\PaymentGateway\Exceptions\PaymentException;
use App\PaymentGateway\GatewayConnectionChecker;
use App\PaymentGateway\PaymentManager;
use App\Services\AdminNotificationService;
use App\Services\Tenant\OrderLifecycleService;
use App\Services\Tenant\StockService;
use App\Services\TenantNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PaymentController
 *
 * Handles the payment lifecycle for storefront customers:
 *   1. charge()  — create a gateway charge for a pending order
 *   2. success() — verify the gateway callback and mark the order as paid
 *   3. cancel()  — handle a user-cancelled payment
 *
 * The controller expects the customer to already be authenticated via the
 * 'auth:storefront' middleware applied to the route group in routes/tenant.php.
 *
 * Payment currency is always USD. Currency conversion is display-only on the
 * storefront and does not affect the amount charged to the gateway.
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentManager $manager,
        private readonly OrderLifecycleService $orderLifecycleService,
        private readonly StockService $stockService,
        private readonly GatewayConnectionChecker $connectionChecker,
        private readonly TenantNotificationService $tenantNotificationService,
        private readonly AdminNotificationService $adminNotificationService,
    ) {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. Charge
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /checkout/payment/{gateway}/{orderUuid}
     *
     * Initiates a payment charge for the given pending order.
     * The order must belong to the authenticated storefront customer and
     * must not already be paid.
     *
     * On success:
     *   - redirects the user to the external gateway checkout page, OR
     *   - renders a hosted payment form view (gateway-dependent).
     *
     * On failure: returns the customer to the checkout page with an error.
     */
    public function charge(Request $request, string $gateway, string $orderUuid): RedirectResponse|View
    {
        $order = Order::where('uuid', $orderUuid)
            ->where('customer_id', auth('storefront')->id())
            ->where('paid', false)
            ->firstOrFail();

        $charge = PaymentCharge::fromArray([
            'amount' => $order->grand_total,
            'currency' => 'USD',   // payment always in USD
            'description' => "Order #{$order->uuid}",
            'order_id' => $order->uuid,
            'email' => auth('storefront')->user()->email,
            'success_url' => route('tenant.payment.success', $gateway),
            'cancel_url' => route('tenant.payment.cancel', $gateway),
        ]);

        $this->orderLifecycleService->recordPaymentInitiated($order, $gateway);
        session(['storefront_pending_payment' => ['uuid' => $order->uuid, 'gateway' => $gateway]]);

        $result = $this->manager->gateway($gateway)->charge($charge);

        if ($result->needsRedirect) {
            return redirect()->away($result->redirectUrl);
        }

        if ($result->needsView) {
            return view($result->viewName, $result->viewData);
        }

        if ($result->success) {
            // For synchronous gateways, we can mark the order as paid immediately.
            $order->update([
                'paid' => true,
                'status' => OrderStatus::Processing,
                'payment_details' => [
                    'transaction_id' => $result->transactionId,
                    'gateway' => $gateway,
                    'raw' => $result->rawResponse,
                ],
            ]);
            $freshOrder = $order->fresh();
            $this->stockService->decrementForOrder($freshOrder);
            $this->orderLifecycleService->recordProcessing($freshOrder);
            $this->notifyAdminPaymentReceived($freshOrder, $gateway);

            $this->markCompanionOrderPaid($result->transactionId, $gateway, $result->rawResponse);

            // Clear cart, coupon, and pending payment marker now that payment is confirmed.
            session()->forget(['storefront_cart', 'storefront_coupon', 'storefront_pending_payment', 'storefront_companion_order_uuid']);

            return redirect()->route('tenant.storefront.order-status', $order->uuid)
                ->with('payment_success', true);
        }

        return redirect()->route('tenant.storefront.checkout')
            ->withErrors(['payment' => $this->customerFacingError($gateway, $result)]);
    }

    private function notifyAdminPaymentReceived(Order $order, string $gateway): void
    {
        $this->adminNotificationService->notify(
            type: 'payment',
            title: 'Plan Payment Received',
            message: sprintf(
                'Tenant "%s" received a payment for order #%s (%s gateway).',
                tenant()?->getTenantKey() ?? 'unknown',
                $order->uuid,
                $gateway,
            ),
            data: ['tenant_id' => tenant()?->getTenantKey(), 'order_number' => $order->uuid, 'gateway' => $gateway],
        );
    }

    /**
     * Never leak raw gateway/provider errors (e.g. "Invalid API Key provided")
     * to the customer. When a failure looks like a broken/misconfigured
     * credential rather than a genuine decline, mask it behind a generic
     * message and alert the vendor so they can fix their gateway settings.
     */
    private function customerFacingError(string $gateway, PaymentResult $result): string
    {
        $genericMessage = __('This payment method is currently unavailable. Please use another payment method.');

        try {
            $config = $this->manager->getConfig($gateway);
        } catch (\Throwable) {
            return $genericMessage;
        }

        $ping = $this->connectionChecker->ping($gateway, $config);

        if ($ping['ok']) {
            // Credentials are valid — this is a genuine decline/customer-side issue, safe to show.
            return $result->errorMessage ?? __('Payment initiation failed. Please try again.');
        }

        $tenantGateway = TenantPaymentGateway::query()->where('code', $gateway)->first();

        if ($tenantGateway && $tenantGateway->use_own) {
            $tenantGateway->update(['connection_status' => 'not_connected']);
        }

        if ($tenant = tenant()) {
            $this->tenantNotificationService->notify(
                $tenant,
                'payment_gateway_error',
                __('Payment gateway misconfigured'),
                __(':gateway is not working (:reason). Customers cannot pay with it until you fix or re-enter its API keys.', [
                    'gateway' => $tenantGateway->name ?? ucfirst($gateway),
                    'reason' => $ping['message'],
                ]),
                ['gateway' => $gateway]
            );
        }

        return $genericMessage;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Success callback
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET|POST /checkout/payment/{gateway}/success
     *
     * Called by the gateway after a successful payment. Verifies the result,
     * then marks the matching order as paid and sets its status to Processing.
     *
     * Accepts both GET and POST because different gateways use different
     * callback methods.
     */
    public function success(Request $request, string $gateway): RedirectResponse
    {
        try {
            $result = $this->manager->gateway($gateway)->verify($request);
        } catch (PaymentException $e) {
            return redirect()->route('tenant.storefront.checkout')
                ->withErrors(['payment' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return redirect()->route('tenant.storefront.checkout')
                ->withErrors(['payment' => __('Payment verification failed. Please contact support.')]);
        }

        if ($result->success) {
            $orderUuid = $result->extra['order_id'] ?? null;

            if ($orderUuid) {
                $order = Order::query()
                    ->where('uuid', $orderUuid)
                    ->where('paid', false)
                    ->first();

                $order?->update([
                    'paid' => true,
                    'status' => OrderStatus::Processing,
                    'payment_details' => [
                        'transaction_id' => $result->transactionId,
                        'gateway' => $gateway,
                        'raw' => $result->rawResponse,
                    ],
                ]);

                if ($order) {
                    $freshOrder = $order->fresh();
                    $this->stockService->decrementForOrder($freshOrder);
                    $this->orderLifecycleService->recordProcessing($freshOrder);
                    $this->notifyAdminPaymentReceived($freshOrder, $gateway);
                }

                $this->markCompanionOrderPaid($result->transactionId, $gateway, $result->rawResponse);
            }

            // Clear cart, coupon, and pending payment marker now that payment is confirmed.
            session()->forget(['storefront_cart', 'storefront_coupon', 'storefront_pending_payment', 'storefront_companion_order_uuid']);

            return redirect()->route('tenant.storefront.order-status', $orderUuid ?? '')
                ->with('payment_success', true);
        }

        return redirect()->route('tenant.storefront.checkout')
            ->withErrors(['payment' => $this->customerFacingError($gateway, $result)]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. Cancel
    // ─────────────────────────────────────────────────────────────────────────

    private function markCompanionOrderPaid(?string $transactionId, string $gateway, mixed $raw): void
    {
        $companionUuid = session('storefront_companion_order_uuid');

        if (!$companionUuid) {
            return;
        }

        $companion = Order::query()->where('uuid', $companionUuid)->where('paid', false)->first();

        if (!$companion) {
            return;
        }

        $companion->update([
            'paid' => true,
            'status' => OrderStatus::Processing,
            'payment_details' => [
                'transaction_id' => $transactionId,
                'gateway' => $gateway,
                'raw' => $raw,
                'note' => 'Paid as companion order in split checkout',
            ],
        ]);

        $fresh = $companion->fresh();
        $this->stockService->decrementForOrder($fresh);
        $this->orderLifecycleService->recordProcessing($fresh);
    }

    /**
     * GET /checkout/payment/{gateway}/cancel
     *
     * Called when the user cancels the payment at the external gateway.
     * The order remains in Pending / unpaid state; the customer can retry.
     */
    public function cancel(Request $request, string $gateway): RedirectResponse
    {
        $pending = session('storefront_pending_payment');

        if ($pending && isset($pending['uuid'])) {
            $order = Order::query()
                ->where('uuid', $pending['uuid'])
                ->where('paid', false)
                ->first();

            if ($order) {
                $this->orderLifecycleService->recordPaymentCancelled($order, $pending['gateway'] ?? $gateway);
            }
        }

        session()->forget('storefront_pending_payment');

        return redirect()->route('tenant.storefront.checkout')
            ->with('payment_cancelled', true);
    }
}
