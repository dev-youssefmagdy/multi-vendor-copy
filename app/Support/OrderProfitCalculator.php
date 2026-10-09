<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Tenant\Order as TenantOrder;

/**
 * Canonical "effective owner profit" / "effective tenant profit" calculation.
 * Mirrors TenantLedgerService / OrderRepository / TenantAdminAggregateService.
 *
 * Refunds & cancellations (RETURN_EXCHANGE_REFUND_PLAN.md B.4 "Financial impact"):
 * the *ForOrder() helpers treat a cancelled / rejected / refunded order as producing no
 * money for anybody (owner profit, tenant profit and both payout directions are 0), and
 * base the tenant's share on the net amount kept from the customer
 * (grand_total − refunded_amount). Partial refunds are borne by the tenant's share; the
 * owner profit (central's cut) is unchanged by them.
 */
class OrderProfitCalculator
{
    /** Order statuses that never produce revenue. */
    public const VOID_STATUSES = [OrderStatus::Cancelled, OrderStatus::Rejected, OrderStatus::Refunded];

    /**
     * The order produces no revenue: cancelled, rejected, refunded, or its payment has been
     * refunded in full.
     */
    public static function isFinanciallyVoid(TenantOrder $order): bool
    {
        if (in_array($order->status, self::VOID_STATUSES, true)) {
            return true;
        }

        $refunded = round((float) ($order->refunded_amount ?? 0), 2);

        return $refunded > 0 && $refunded >= round((float) $order->grand_total, 2);
    }

    /** Money kept from the customer: grand_total − completed refunds (0 for void orders). */
    public static function netOrderTotal(TenantOrder $order): float
    {
        if (self::isFinanciallyVoid($order)) {
            return 0.0;
        }

        return max(0.0, round((float) $order->grand_total - (float) ($order->refunded_amount ?? 0), 2));
    }

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
        if (self::isFinanciallyVoid($order)) {
            return 0.0;
        }

        return self::effectiveOwnerProfit($order->vendor_gateway_id, $order->vendor_cost, $order->owner_profit);
    }

    public static function effectiveTenantProfitForOrder(TenantOrder $order): float
    {
        if (self::isFinanciallyVoid($order)) {
            return 0.0;
        }

        return self::effectiveTenantProfit(
            $order->vendor_gateway_id,
            $order->vendor_cost,
            $order->owner_profit,
            self::netOrderTotal($order)
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
        if (self::isFinanciallyVoid($order)) {
            return 0.0;
        }

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
        if (self::isFinanciallyVoid($order)) {
            return 0.0;
        }

        return self::centralOwnTenant(
            $order->vendor_gateway_id,
            $order->vendor_cost,
            $order->owner_profit,
            self::netOrderTotal($order)
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
