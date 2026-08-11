<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Tenant;
use App\Models\Tenant\Order;
use App\Models\Tenant\PaymentGateway;
use App\Services\Mail\TemplateMailService;

/**
 * Checks whether a tenant's cumulative paid-order sales have crossed the
 * configured gateway-usage thresholds and takes the appropriate action:
 *
 *  - notified_sales_amount       → warning email, flag gateway_limit_warned = true
 *  - notified_sales_amount_block → block email, set gateway_payment_blocked = true
 *                                  (storefront is suspended until vendor configures own gateway)
 *
 * This service is designed to be called from within a tenant context
 * (e.g. inside TenantOrderObserver) where the tenant DB connection is active.
 */
class TenantGatewayLimitService
{
    public function __construct(
        private readonly TemplateMailService $mailService,
    ) {
    }

    /**
     * Entry point: called after a paid order is saved inside a tenant context.
     */
    public function checkAndNotify(Tenant $tenant): void
    {
        // Fetch both thresholds from the central settings table.
        [$warningAmount, $blockAmount] = $this->fetchThresholds();

        // Nothing configured — skip.
        if ($warningAmount <= 0 && $blockAmount <= 0) {
            return;
        }

        // If the tenant already has at least one active gateway using their own
        // credentials, the platform gateway is not involved — skip all checks.
        if ($this->tenantHasOwnGateway()) {
            return;
        }

        $data = (array) ($tenant->data ?? []);

        // If already blocked, nothing more to do.
        if ($data['gateway_payment_blocked'] ?? false) {
            return;
        }

        $totalSales = $this->calculateTotalPaidSales();

        // Block threshold takes priority.
        if ($blockAmount > 0 && $totalSales >= $blockAmount) {
            $this->blockTenant($tenant, $totalSales, $blockAmount);
            return;
        }

        // Warning threshold.
        if ($warningAmount > 0 && $totalSales >= $warningAmount && !($data['gateway_limit_warned'] ?? false)) {
            $this->warnTenant($tenant, $totalSales, $warningAmount);
        }
    }

    /**
     * Unblock a tenant whose gateway_payment_blocked flag is set.
     * Called automatically when the tenant enables their own gateway credentials.
     */
    public function unblockIfConfigured(Tenant $tenant): void
    {
        $data = (array) ($tenant->data ?? []);

        if (!($data['gateway_payment_blocked'] ?? false)) {
            return;
        }

        if (!$this->tenantHasOwnGateway()) {
            return;
        }

        $data['gateway_payment_blocked'] = false;

        tenancy()->central(function () use ($tenant, $data) {
            $tenant->update(['data' => $data]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function fetchThresholds(): array
    {
        $settings = tenancy()->central(fn() => AppSetting::query()
            ->whereIn('key', ['notified_sales_amount', 'notified_sales_amount_block'])
            ->get()
            ->keyBy('key'));

        $warning = (float) ($settings->get('notified_sales_amount')?->value ?? 0);
        $block = (float) ($settings->get('notified_sales_amount_block')?->value ?? 0);

        return [$warning, $block];
    }

    private function tenantHasOwnGateway(): bool
    {
        return PaymentGateway::query()
            ->where('is_active', true)
            ->where('use_own', true)
            ->exists();
    }

    /**
     * Calculates the cumulative grand total of all paid orders in the current
     * tenant database.  Orders are loaded with items so that the computed
     * grand_total accessor (which sums item subtotals plus adjustments) works.
     */
    private function calculateTotalPaidSales(): float
    {
        return (float) Order::query()
            ->where('paid', true)
            ->with('items')
            ->get()
            ->sum(fn(Order $order) => $order->grand_total);
    }

    private function warnTenant(Tenant $tenant, float $totalSales, float $limit): void
    {
        $sent = $this->mailService->sendGatewayLimitWarning($tenant, $totalSales, $limit);

        if ($sent) {
            $data = (array) ($tenant->data ?? []);
            $data['gateway_limit_warned'] = true;
            $data['gateway_limit_warned_at'] = now()->toISOString();

            tenancy()->central(function () use ($tenant, $data) {
                $tenant->update(['data' => $data]);
            });
        }
    }

    private function blockTenant(Tenant $tenant, float $totalSales, float $blockAmount): void
    {
        $sent = $this->mailService->sendGatewayLimitBlock($tenant, $totalSales, $blockAmount);

        $data = (array) ($tenant->data ?? []);
        $data['gateway_payment_blocked'] = true;
        $data['gateway_payment_blocked_at'] = now()->toISOString();

        // Always persist the block flag regardless of mail delivery.
        tenancy()->central(function () use ($tenant, $data) {
            $tenant->update(['data' => $data]);
        });
    }
}
