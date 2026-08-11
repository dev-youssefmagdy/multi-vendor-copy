<?php

namespace App\Support;

use App\Models\Tenant\Order as TenantOrder;

/**
 * Canonical "effective owner profit" / "effective tenant profit" calculation.
 * Mirrors TenantLedgerService / OrderRepository / TenantAdminAggregateService.
 */
class OrderProfitCalculator
{
    public static function effectiveOwnerProfit(?int $vendorGatewayId, ?float $vendorCost, ?float $ownerProfit): float
    {
        return (float) ($vendorGatewayId !== null ? $vendorCost : ($ownerProfit ?? 0));
    }

    public static function effectiveTenantProfit(?int $vendorGatewayId, ?float $vendorCost, ?float $ownerProfit, float $orderTotal): float
    {
        return $orderTotal - self::effectiveOwnerProfit($vendorGatewayId, $vendorCost, $ownerProfit);
    }

    public static function effectiveOwnerProfitForOrder(TenantOrder $order): float
    {
        return self::effectiveOwnerProfit($order->vendor_gateway_id, $order->vendor_cost, $order->owner_profit);
    }

    public static function effectiveTenantProfitForOrder(TenantOrder $order): float
    {
        return self::effectiveTenantProfit(
            $order->vendor_gateway_id,
            $order->vendor_cost,
            $order->owner_profit,
            (float) $order->grand_total
        );
    }

    public static function orderPaidFromTenantGateway(?int $vendorGatewayId): bool
    {
        return $vendorGatewayId !== null;
    }

    public static function orderPaidFromTenantGatewayForOrder(TenantOrder $order): bool
    {
        return self::orderPaidFromTenantGateway($order->vendor_gateway_id);
    }

    public static function tenantOwnCentral(?int $vendorGatewayId, ?float $vendorCost, ?float $ownerProfit): float
    {
        return self::orderPaidFromTenantGateway($vendorGatewayId)
            ? self::effectiveOwnerProfit($vendorGatewayId, $vendorCost, $ownerProfit)
            : 0.0;
    }

    public static function tenantOwnCentralForOrder(TenantOrder $order): float
    {
        return self::tenantOwnCentral($order->vendor_gateway_id, $order->vendor_cost, $order->owner_profit);
    }

    public static function centralOwnTenant(?int $vendorGatewayId, ?float $vendorCost, ?float $ownerProfit, float $orderTotal): float
    {
        return self::orderPaidFromTenantGateway($vendorGatewayId)
            ? 0.0
            : self::effectiveTenantProfit($vendorGatewayId, $vendorCost, $ownerProfit, $orderTotal);
    }

    public static function centralOwnTenantForOrder(TenantOrder $order): float
    {
        return self::centralOwnTenant(
            $order->vendor_gateway_id,
            $order->vendor_cost,
            $order->owner_profit,
            (float) $order->grand_total
        );
    }

    /**
     * What the tenant still owes central right now: tenantOwnCentral, zeroed out
     * once the vendor has settled payment for this order.
     */
    public static function remainingTenantOwnCentralForOrder(TenantOrder $order): float
    {
        return $order->vendor_settled ? 0.0 : self::tenantOwnCentralForOrder($order);
    }

    /**
     * What central still owes the tenant right now: centralOwnTenant, zeroed out
     * once the tenant's payout for this order has been released.
     */
    public static function remainingCentralOwnTenantForOrder(TenantOrder $order): float
    {
        return ($order->payout_released ?? false) ? 0.0 : self::centralOwnTenantForOrder($order);
    }
}
