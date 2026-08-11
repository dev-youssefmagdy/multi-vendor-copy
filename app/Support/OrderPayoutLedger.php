<?php

namespace App\Support;

use App\Models\TenantPayout;
use App\Models\VendorSettlement;
use App\Models\Tenant\Order as TenantOrder;

/**
 * DB-aware "Remaining" sums, built on top of OrderProfitCalculator's pure formulas.
 */
class OrderPayoutLedger
{
    public static function vendorPaymentsForOrder(int $orderId): float
    {
        return (float) VendorSettlement::query()
            ->where('order_id', $orderId)
            ->where('status', 'paid')
            ->sum('total');
    }

    public static function vendorPaymentsForTenant(string $tenantId): float
    {
        return (float) VendorSettlement::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->sum('total');
    }

    public static function centralPayoutsForTenant(string $tenantId): float
    {
        return (float) TenantPayout::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->sum('amount');
    }

    public static function remainingOwnerProfitForOrder(TenantOrder $order): float
    {
        return OrderProfitCalculator::tenantOwnCentralForOrder($order)
            - self::vendorPaymentsForOrder($order->id);
    }

    public static function remainingTenantProfitForTenant(string $tenantId, float $centralOwnTenantSum): float
    {
        return $centralOwnTenantSum - self::centralPayoutsForTenant($tenantId);
    }
}
