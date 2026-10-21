<?php

namespace App\Support;

use App\Models\Tenant\Order as TenantOrder;
use App\Models\TenantPayout;
use App\Models\VendorSettlement;
use Illuminate\Support\Collection;

/**
 * DB-aware settlement helpers built on OrderProfitCalculator (docs/ORDER_FORMULAS.md section 3).
 *
 * Orders live in the TENANT database; vendor -> platform payments are the central Settlement Payments
 * (vendor_settlements) records; platform -> vendor payouts are tracked by orders.payout_released. Methods documented "tenant context"
 * must run with tenancy initialised; the *ForTenant methods switch context themselves, so they can be
 * called from central code (admin panel) or from the vendor panel.
 *
 * Only paid, non-void orders are counted in the tenant-wide sums.
 */
class OrderPayoutLedger
{
    // ─────────────────────────────────────────────────────────────────────
    // Tenancy helper
    // ─────────────────────────────────────────────────────────────────────

    /** Run $callback inside the tenant DB context and restore the previous context afterwards. */
    public static function inTenant(string $tenantId, callable $callback): mixed
    {
        $tenancy = tenancy();
        $previous = $tenancy->initialized ? $tenancy->tenant : null;

        if ($previous && (string) $previous->getTenantKey() === $tenantId) {
            return $callback();
        }

        $tenancy->initialize($tenantId);
        try {
            return $callback();
        } finally {
            $tenancy->end();
            if ($previous) {
                $tenancy->initialize($previous);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Per-order (tenant context)
    // ─────────────────────────────────────────────────────────────────────

    /** vendor_own_platform for one order (what the vendor still owes the platform). */
    public static function vendorOwnPlatformForOrder(TenantOrder $order): float
    {
        return OrderProfitCalculator::vendorOwnPlatformForOrder($order);
    }

    /** platform_own_vendor for one order (what the platform still owes the vendor). */
    public static function platformOwnVendorForOrder(TenantOrder $order): float
    {
        return OrderProfitCalculator::platformOwnVendorForOrder($order);
    }

    /** Σ of vendor_own_platform over the given orders (tenant context). */
    public static function sumVendorOwnPlatform(iterable $orders): float
    {
        $sum = 0.0;
        foreach ($orders as $order) {
            $sum += OrderProfitCalculator::vendorOwnPlatformForOrder($order);
        }

        return round($sum, 2);
    }

    /** Σ of platform_own_vendor over the given orders (tenant context). */
    public static function sumPlatformOwnVendor(iterable $orders): float
    {
        $sum = 0.0;
        foreach ($orders as $order) {
            $sum += OrderProfitCalculator::platformOwnVendorForOrder($order);
        }

        return round($sum, 2);
    }

    /** Vendor-collected, paid orders that still owe the platform something (tenant context). */
    public static function ordersOwingPlatform(): Collection
    {
        return TenantOrder::query()
            ->where('collected_by', 'vendor')
            ->where('paid', true)
            ->get()
            ->filter(fn (TenantOrder $o) => OrderProfitCalculator::vendorOwnPlatformForOrder($o) > 0)
            ->values();
    }

    /** Platform-collected, paid orders the platform still owes the vendor for (tenant context). */
    public static function ordersOwedByPlatform(bool $onlyUnreleased = false): Collection
    {
        return TenantOrder::query()
            ->where('collected_by', 'platform')
            ->where('paid', true)
            ->when($onlyUnreleased, fn ($q) => $q->where('payout_released', false))
            ->get()
            ->filter(fn (TenantOrder $o) => OrderProfitCalculator::platformOwnVendorForOrder($o) > 0)
            ->values();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Tenant-wide sums (switch to the tenant DB themselves)
    // ─────────────────────────────────────────────────────────────────────

    /** Total the vendor still owes the platform (vendor-collected orders). */
    public static function vendorOwnPlatformForTenant(string $tenantId): float
    {
        return (float) self::inTenant($tenantId, fn () => self::sumVendorOwnPlatform(self::ordersOwingPlatform()));
    }

    /** Total the platform still owes the vendor (platform-collected orders). */
    public static function platformOwnVendorForTenant(string $tenantId): float
    {
        return (float) self::inTenant($tenantId, fn () => self::sumPlatformOwnVendor(self::ordersOwedByPlatform()));
    }

    // ─────────────────────────────────────────────────────────────────────
    // Writing transactions (tenant context)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Sync the legacy vendor_settled_* columns after a vendor -> platform payment of $principal.
     * The ledger entry itself is the central VendorSettlement (Settlement Payments) record that the
     * caller creates; vendor_settled_at is set only when nothing remains owed.
     * Returns the principal that is accounted for (capped at the remaining amount).
     */
    public static function recordVendorSettlement(
        TenantOrder $order,
        ?float $principal,
        float $gatewayFee,
        ?string $reference,
        ?int $gatewayId = null,
    ): float {
        $remaining = OrderProfitCalculator::vendorOwnPlatformForOrder($order);
        $amount = round(min($principal ?? $remaining, $remaining), 2);

        if ($amount <= 0) {
            return 0.0;
        }

        $attrs = [
            'vendor_gateway_fee' => round((float) $order->vendor_gateway_fee + $gatewayFee, 2),
            'vendor_settled_at' => round($remaining - $amount, 2) <= 0 ? now() : null,
        ];
        if ($reference !== null && $reference !== '') {
            $attrs['vendor_settlement_ref'] = $reference;
        }
        if ($gatewayId !== null) {
            $attrs['vendor_gateway_id'] = $gatewayId;
        }
        $order->forceFill($attrs)->save();

        return $amount;
    }

    /** Admin correction: drop the order's Settlement Payments records and clear the legacy columns. */
    public static function clearVendorSettlements(TenantOrder $order): void
    {
        $tenantId = (string) tenant('id');
        self::central(fn () => VendorSettlement::query()
            ->where('tenant_id', $tenantId)->where('order_id', $order->id)->delete());

        $order->forceFill([
            'vendor_gateway_id' => null,
            'vendor_gateway_fee' => 0,
            'vendor_settled_at' => null,
            'vendor_settlement_ref' => null,
        ])->save();
    }

    /**
     * Flag the order's payout as released (platform -> vendor). Returns the amount paid out
     * (0 when nothing was owed).
     */
    public static function recordPlatformPayout(TenantOrder $order, ?string $reference = null, ?string $note = null): float
    {
        $amount = OrderProfitCalculator::platformOwnVendorForOrder($order);
        $order->forceFill(['payout_released' => true])->save();

        return $amount;
    }

    /**
     * Release every unreleased platform-collected paid order of the CURRENT tenant.
     *
     * @return array<int,array{order_id:int,uuid:string,amount:float}> released orders (payout snapshot)
     */
    public static function releasePayoutsForCurrentTenant(?string $reference = null): array
    {
        $released = [];
        $orders = TenantOrder::query()
            ->where('collected_by', 'platform')
            ->where('paid', true)
            ->where('payout_released', false)
            ->get();

        foreach ($orders as $order) {
            if (OrderProfitCalculator::isFinanciallyVoid($order)) {
                continue;
            }
            $amount = self::recordPlatformPayout($order, $reference);
            $released[] = ['order_id' => $order->id, 'uuid' => $order->uuid, 'amount' => $amount];
        }

        return $released;
    }

    /** Release payouts for a tenant from central code. @see releasePayoutsForCurrentTenant */
    public static function releasePayoutsForTenant(string $tenantId, ?string $reference = null): array
    {
        return self::inTenant($tenantId, fn () => self::releasePayoutsForCurrentTenant($reference));
    }

    /** Undo payouts: un-release the given orders. */
    public static function reversePayoutsForTenant(string $tenantId, array $orderUuids): void
    {
        if ($orderUuids === []) {
            return;
        }

        self::inTenant($tenantId, function () use ($orderUuids) {
            TenantOrder::query()->whereIn('uuid', $orderUuids)->update(['payout_released' => false]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Central invoice-record sums (VendorSettlement / TenantPayout rows)
    // ─────────────────────────────────────────────────────────────────────

    /** Run a central-DB query from either central or tenant context. */
    private static function central(callable $callback): mixed
    {
        return tenancy()->initialized ? tenancy()->central($callback) : $callback();
    }

    /** Σ principal (product_cost) of the paid Settlement Payments of one tenant order. */
    public static function vendorPaymentsForOrder(int $orderId): float
    {
        // Resolve the tenant id BEFORE switching to the central context (tenant() is empty inside it).
        $tenantId = (string) tenant('id');

        return (float) self::central(fn () => VendorSettlement::query()
            ->where('tenant_id', $tenantId)
            ->where('order_id', $orderId)
            ->whereIn('status', ['paid', 'completed'])
            ->sum('product_cost'));
    }

    public static function vendorPaymentsForTenant(string $tenantId): float
    {
        return (float) VendorSettlement::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total');
    }

    public static function centralPayoutsForTenant(string $tenantId): float
    {
        return (float) TenantPayout::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->sum('amount');
    }
}
