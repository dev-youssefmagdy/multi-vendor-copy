<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Mail\VendorSettlementAdminMail;
use App\Mail\VendorSettlementMail;
use App\Models\AdminUser as CentralAdminUser;
use App\Models\Tenant;
use App\Models\Tenant\AdminUser;
use App\Models\Tenant\Order;
use App\Models\VendorSettlement;
use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\Exceptions\PaymentException;
use App\PaymentGateway\PaymentManager;
use App\Services\Tenant\VendorPurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * VendorSettlementPaymentController
 *
 * Handles the real-money payment flow when a tenant (vendor) settles what they
 * owe the central platform for product cost + shipping on a fulfilled order.
 *
 * Flow:
 *   1. VendorPurchasePage selects a gateway & redirects here (charge)
 *   2. Controller initiates charge via the CENTRAL gateway credentials
 *   3. Gateway redirects back to success / cancel
 *   4. On success: order marked settled, VendorSettlement record created, email sent
 *   5. Redirect to vendor-purchases page with session toast
 */
class VendorSettlementPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentManager $manager,
        private readonly VendorPurchaseService $purchaseService,
    ) {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. Charge
    // ─────────────────────────────────────────────────────────────────────────

    public function charge(Request $request, string $gateway, int $orderId): RedirectResponse|View
    {
        /** @var AdminUser $admin */
        $admin = auth('tenant')->user();
        $tenant = tenant();

        $order = Order::query()
            ->where('id', $orderId)
            ->whereNotNull('vendor_cost')
            ->whereNull('vendor_settled_at')
            ->firstOrFail();

        // Resolve central gateway model for fee calculation
        $centralGatewayModel = tenancy()->central(
            fn() => \App\Models\PaymentGateway::query()
                ->where('code', $gateway)
                ->where('status', 'active')
                ->first()
        );

        if (!$centralGatewayModel) {
            return redirect()->route('tenant.finance.vendor-purchases')
                ->withErrors(['payment' => 'Selected payment gateway is not available.']);
        }

        $breakdown = $this->purchaseService->previewBreakdown($order, $centralGatewayModel);
        $amount = max($breakdown['total'], 0.01);

        $charge = PaymentCharge::fromArray([
            'amount' => $amount,
            'currency' => 'USD',
            'description' => "Vendor settlement – Order #{$order->uuid}",
            'order_id' => (string) $orderId,
            'email' => $admin->email ?? ($tenant->email ?? 'vendor@example.com'),
            'name' => $admin->name ?? $tenant->id,
            'success_url' => route('tenant.vendor-settlement.success', $gateway) . '?order_id=' . $orderId,
            'cancel_url' => route('tenant.vendor-settlement.cancel', $gateway),
        ]);

        session([
            'tenant_vendor_settlement_pending' => [
                'order_id' => $orderId,
                'gateway_code' => $gateway,
                'gateway_id' => $centralGatewayModel->id,
                'breakdown' => $breakdown,
                'admin_email' => $admin->email,
                'admin_name' => $admin->name,
            ],
        ]);

        try {
            $result = $this->manager->centralGateway($gateway)->charge($charge);
        } catch (PaymentException $e) {
            return redirect()->route('tenant.finance.vendor-purchases')
                ->withErrors(['payment' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return redirect()->route('tenant.finance.vendor-purchases')
                ->withErrors(['payment' => 'Payment initiation failed. Please try again.']);
        }

        if ($result->needsRedirect) {
            return redirect()->away($result->redirectUrl);
        }

        if ($result->needsView) {
            return view($result->viewName, $result->viewData);
        }

        if ($result->success) {
            return $this->handleSuccess(
                $tenant,
                $order,
                $centralGatewayModel,
                $breakdown,
                $result->transactionId,
                $admin->email,
                $admin->name,
            );
        }

        return redirect()->route('tenant.finance.vendor-purchases')
            ->withErrors(['payment' => $result->errorMessage ?? 'Payment initiation failed. Please try again.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Success callback
    // ─────────────────────────────────────────────────────────────────────────

    public function success(Request $request, string $gateway): RedirectResponse
    {
        $pending = session('tenant_vendor_settlement_pending');

        try {
            $result = $this->manager->centralGateway($gateway)->verify($request);
        } catch (PaymentException $e) {
            return redirect()->route('tenant.finance.vendor-purchases')
                ->withErrors(['payment' => $e->getMessage()]);
        } catch (\Throwable) {
            return redirect()->route('tenant.finance.vendor-purchases')
                ->withErrors(['payment' => 'Payment verification failed. Please contact support.']);
        }

        if (!$result->success) {
            return redirect()->route('tenant.finance.vendor-purchases')
                ->withErrors(['payment' => $result->errorMessage ?? 'Payment was not successful.']);
        }

        $orderId = $request->integer('order_id') ?: ($pending['order_id'] ?? null);
        $breakdown = $pending['breakdown'] ?? [];
        $adminEmail = $pending['admin_email'] ?? null;
        $adminName = $pending['admin_name'] ?? null;

        if (!$orderId) {
            return redirect()->route('tenant.finance.vendor-purchases')
                ->withErrors(['payment' => 'Settlement reference lost. Please contact support.']);
        }

        $order = Order::query()
            ->where('id', $orderId)
            ->whereNotNull('vendor_cost')
            ->first();

        if (!$order) {
            return redirect()->route('tenant.finance.vendor-purchases')
                ->withErrors(['payment' => 'Order not found.']);
        }

        $centralGatewayModel = tenancy()->central(
            fn() => \App\Models\PaymentGateway::query()
                ->where('code', $gateway)
                ->where('status', 'active')
                ->first()
        );

        if (!$centralGatewayModel && !empty($pending['gateway_id'])) {
            $centralGatewayModel = tenancy()->central(
                fn() => \App\Models\PaymentGateway::query()->find($pending['gateway_id'])
            );
        }

        // Re-calculate breakdown if not in session
        if (empty($breakdown)) {
            $breakdown = $this->purchaseService->previewBreakdown($order, $centralGatewayModel);
        }

        $tenant = tenant();

        return $this->handleSuccess(
            $tenant,
            $order,
            $centralGatewayModel,
            $breakdown,
            $result->transactionId,
            $adminEmail,
            $adminName,
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. Cancel
    // ─────────────────────────────────────────────────────────────────────────

    public function cancel(Request $request, string $gateway): RedirectResponse
    {
        session()->forget('tenant_vendor_settlement_pending');

        return redirect()->route('tenant.finance.vendor-purchases')
            ->with('vendor_settlement_error', 'Payment was cancelled.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal
    // ─────────────────────────────────────────────────────────────────────────

    private function handleSuccess(
        Tenant $tenant,
        Order $order,
        ?\App\Models\PaymentGateway $centralGatewayModel,
        array $breakdown,
        ?string $transactionId,
        ?string $adminEmail,
        ?string $adminName,
    ): RedirectResponse {
        session()->forget('tenant_vendor_settlement_pending');

        $gatewayFee = (float) ($breakdown['gateway_fee'] ?? 0);
        $reference = $transactionId ?? ('manual-' . now()->timestamp);

        // Mark order as settled (writes to tenant DB – already in tenant context)
        $this->purchaseService->settle(
            $order,
            $centralGatewayModel ?? new \App\Models\PaymentGateway(),
            $gatewayFee,
            $reference,
        );

        // Create central settlement invoice record
        $settlement = tenancy()->central(function () use ($tenant, $order, $breakdown, $centralGatewayModel, $transactionId, $adminEmail, $adminName) {
            $invoiceNumber = VendorSettlement::nextInvoiceNumber($tenant->getTenantKey());

            return VendorSettlement::create([
                'tenant_id' => $tenant->getTenantKey(),
                'order_id' => $order->id,
                'order_uuid' => $order->uuid,
                'invoice_number' => $invoiceNumber,
                'tenant_name' => $tenant->name ?? $adminName,
                'tenant_email' => $tenant->email ?? $adminEmail,
                'product_cost' => $breakdown['product_cost'] ?? 0,
                'shipping_cost' => $breakdown['shipping_cost'] ?? 0,
                'gateway_fee' => $breakdown['gateway_fee'] ?? 0,
                'total' => $breakdown['total'] ?? 0,
                'gateway_code' => $centralGatewayModel?->code,
                'transaction_id' => $transactionId,
                'status' => 'paid',
                'settled_at' => now(),
                'meta' => [
                    'order_uuid' => $order->uuid,
                    'gateway_name' => $centralGatewayModel?->name,
                ],
            ]);
        });

        // Send confirmation email to tenant admin (vendor)
        $recipientEmail = $adminEmail ?? ($tenant->email ?? null);
        if ($recipientEmail && $settlement) {
            try {
                Mail::to($recipientEmail)->send(new VendorSettlementMail($settlement, $breakdown));
            } catch (\Throwable) {
                // Non-critical – settlement is already recorded
            }
        }

        // Notify central platform admins
        if ($settlement) {
            try {
                $settlementsUrl = route('admin.vendor-settlements.index');
                $centralAdmins = tenancy()->central(
                    fn() => CentralAdminUser::query()->where('status', 'active')->pluck('email')
                );
                foreach ($centralAdmins as $adminMail) {
                    Mail::to($adminMail)->send(new VendorSettlementAdminMail($settlement, $settlementsUrl));
                }
            } catch (\Throwable) {
                // Non-critical
            }
        }

        return redirect()->route('tenant.finance.vendor-purchases')
            ->with('vendor_settlement_success', "Settlement #{$settlement?->invoice_number} recorded. Payment confirmed.");
    }
}
