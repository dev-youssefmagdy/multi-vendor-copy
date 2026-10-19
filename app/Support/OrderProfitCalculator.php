<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Tenant\Order as TenantOrder;

/**
 * Per-order platform / vendor figures (docs/ORDER_FORMULAS.md sections 2-3).
 *
 *   platform_profit / vendor_profit are stored on the order at checkout (see OrderPricing::order()).
 *   vendor_own_platform = platform_profit - Σ vendor_settlement transactions   (collected_by = vendor)
 *   platform_own_vendor = vendor_profit   - Σ platform_payout transactions     (collected_by = platform)
 *
 * Refunds & cancellations (RETURN_EXCHANGE_REFUND_PLAN.md B.4): a cancelled / rejected / refunded order
 * produces no money for anybody; a partial refund is borne by the vendor's share only.
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

    /** The customer's money was received by the vendor (own gateway / COD) rather than the platform. */
    public static function collectedByVendor(TenantOrder $order): bool
    {
        return $order->collected_by === 'vendor';
    }

    /** Platform's share of the order (0 when void). */
    public static function platformProfitForOrder(TenantOrder $order): float
    {
        return self::isFinanciallyVoid($order) ? 0.0 : round((float) $order->platform_profit, 2);
    }

    /** Vendor's share of the order, less partial refunds (0 when void). */
    public static function vendorProfitForOrder(TenantOrder $order): float
    {
        if (self::isFinanciallyVoid($order)) {
            return 0.0;
        }

        return round((float) $order->vendor_profit - (float) ($order->refunded_amount ?? 0), 2);
    }

    /** vendor_own_platform: what the vendor still has to pay the platform for this order. */
    public static function vendorOwnPlatformForOrder(TenantOrder $order): float
    {
        if (! self::collectedByVendor($order)) {
            return 0.0;
        }

        return max(0.0, round(self::platformProfitForOrder($order) - $order->vendor_settlements_paid, 2));
    }

    /** platform_own_vendor: what the platform still has to pay the vendor for this order. */
    public static function platformOwnVendorForOrder(TenantOrder $order): float
    {
        if (self::collectedByVendor($order)) {
            return 0.0;
        }

        return max(0.0, round(self::vendorProfitForOrder($order) - $order->platform_payouts_paid, 2));
    }

    /** Total vendor -> platform amount that is due for the order (before settlements), 0 unless vendor-collected. */
    public static function platformShareDueFromVendor(TenantOrder $order): float
    {
        return self::collectedByVendor($order) ? self::platformProfitForOrder($order) : 0.0;
    }

    /** Total platform -> vendor amount that is due for the order (before payouts), 0 unless platform-collected. */
    public static function vendorShareDueFromPlatform(TenantOrder $order): float
    {
        return self::collectedByVendor($order) ? 0.0 : max(0.0, self::vendorProfitForOrder($order));
    }
}
