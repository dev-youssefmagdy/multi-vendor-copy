<?php

namespace App\Support;

use App\Models\Tenant\Order as TenantOrder;
use App\Models\Tenant\OrderTransaction;
use App\Models\TenantPayout;
use App\Models\VendorSettlement;
use Illuminate\Support\Collection;

/**
 * DB-aware settlement helpers built on OrderProfitCalculator (docs/ORDER_FORMULAS.md section 3).
 *
 * Orders and order_transactions live in the TENANT database. Methods documented "tenant context"
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
            ->with('transactions')
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
            ->with('transactions')
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
     * Record a vendor -> platform payment. $principal defaults to the full remaining amount.
     * Idempotent per (order, reference). Keeps the legacy vendor_settled_* columns in sync.
     * Returns the transaction, or null when nothing was owed.
     */
    public static function recordVendorSettlement(
        TenantOrder $order,
        ?float $principal,
        float $gatewayFee,
        ?string $reference,
        ?int $gatewayId = null,
        ?string $note = null,
    ): ?OrderTransaction {
        $order->unsetRelation('transactions');
        $remaining = OrderProfitCalculator::vendorOwnPlatformForOrder($order);
        $amount = round(min($principal ?? $remaining, $remaining), 2);

        if ($reference !== null && $reference !== '') {
            $existing = OrderTransaction::query()
                ->where('order_id', $order->id)
                ->where('type', OrderTransaction::TYPE_VENDOR_SETTLEMENT)
                ->where('reference', $reference)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        if ($amount <= 0) {
            return null;
        }

        $tx = OrderTransaction::query()->create([
            'order_id' => $order->id,
            'type' => OrderTransaction::TYPE_VENDOR_SETTLEMENT,
            'amount' => $amount,
            'gateway_fee' => round($gatewayFee, 2),
            'reference' => $reference,
            'gateway_id' => $gatewayId,
            'note' => $note,
        ]);

        self::syncVendorSettledColumns($order, $reference, $gatewayId, $gatewayFee);

        return $tx;
    }

    /** Re-derive vendor_settled_at / ref / gateway columns from the transactions. */
    public static function syncVendorSettledColumns(TenantOrder $order, ?string $reference = null, ?int $gatewayId = null, ?float $gatewayFee = null): void
    {
        $order->unsetRelation('transactions');
        $hasAny = $order->transactions()->where('type', OrderTransaction::TYPE_VENDOR_SETTLEMENT)->exists();
        $fullyPaid = $hasAny && OrderProfitCalculator::vendorOwnPlatformForOrder($order) <= 0;

        if (! $hasAny) {
            $order->forceFill([
                'vendor_gateway_id' => null,
                'vendor_gateway_fee' => 0,
                'vendor_settled_at' => null,
                'vendor_settlement_ref' => null,
            ])->save();

            return;
        }

        $fee = (float) $order->transactions()->where('type', OrderTransaction::TYPE_VENDOR_SETTLEMENT)->sum('gateway_fee');
        $attrs = [
            'vendor_gateway_fee' => round($fee, 2),
            'vendor_settled_at' => $fullyPaid ? ($order->vendor_settled_at ?? now()) : null,
        ];
        if ($reference !== null) {
            $attrs['vendor_settlement_ref'] = $reference;
        }
        if ($gatewayId !== null) {
            $attrs['vendor_gateway_id'] = $gatewayId;
        }
        $order->forceFill($attrs)->save();
    }

    /** Delete all vendor_settlement transactions of an order and clear the legacy columns. */
    public static function clearVendorSettlements(TenantOrder $order): void
    {
        OrderTransaction::query()
            ->where('order_id', $order->id)
            ->where('type', OrderTransaction::TYPE_VENDOR_SETTLEMENT)
            ->delete();
        self::syncVendorSettledColumns($order);
    }

    /**
     * Record a platform -> vendor payout of the order's remaining amount and flag payout_released.
     * Returns the amount paid out (0 when nothing was owed).
     */
    public static function recordPlatformPayout(TenantOrder $order, ?string $reference = null, ?string $note = null): float
    {
        $order->unsetRelation('transactions');
        $amount = OrderProfitCalculator::platformOwnVendorForOrder($order);

        if ($amount > 0) {
            OrderTransaction::query()->create([
                'order_id' => $order->id,
                'type' => OrderTransaction::TYPE_PLATFORM_PAYOUT,
                'amount' => $amount,
                'gateway_fee' => 0,
                'reference' => $reference,
                'note' => $note,
            ]);
        }

        $order->forceFill(['payout_released' => true])->save();
        $order->unsetRelation('transactions');

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
            ->with('transactions')
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

    /** Undo payouts: delete platform_payout transactions of the given order uuids and un-release them. */
    public static function reversePayoutsForTenant(string $tenantId, array $orderUuids): void
    {
        if ($orderUuids === []) {
            return;
        }

        self::inTenant($tenantId, function () use ($orderUuids) {
            $ids = TenantOrder::query()->whereIn('uuid', $orderUuids)->pluck('id');
            OrderTransaction::query()
                ->whereIn('order_id', $ids)
                ->where('type', OrderTransaction::TYPE_PLATFORM_PAYOUT)
                ->delete();
            TenantOrder::query()->whereIn('id', $ids)->update(['payout_released' => false]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Central invoice-record sums (VendorSettlement / TenantPayout rows)
    // ─────────────────────────────────────────────────────────────────────

    public static function vendorPaymentsForOrder(int $orderId): float
    {
        return (float) VendorSettlement::query()
            ->where('order_id', $orderId)
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total');
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
