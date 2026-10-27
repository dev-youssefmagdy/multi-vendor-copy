<?php

namespace App\Services\Tenant;

use App\Models\PaymentGateway as CentralGateway;
use App\Models\Tenant\Order;
use App\Support\OrderPayoutLedger;
use App\Support\OrderProfitCalculator;

/**
 * Handles the calculation and settlement of what the vendor owes the platform.
 *
 * Flow (docs/ORDER_FORMULAS.md section 3):
 *   1. For a vendor-collected order the vendor owes the platform its platform_profit
 *      (OrderProfitCalculator::vendorOwnPlatformForOrder = platform_profit - settlements already paid).
 *   2. The vendor picks a central payment gateway; its fee is computed on the amount owed.
 *   3. On success a Settlement Payments (vendor_settlements) record is created by the caller and the legacy
 *      vendor_settled_* columns are synced (vendor_settled_at only once nothing remains).
 */
class VendorPurchaseService
{
    /**
     * Calculate the fee for a given central gateway and total-due amount.
     */
    public function gatewayFee(CentralGateway $gateway, float $totalDue): float
    {
        return $gateway->calculateFee($totalDue);
    }

    /**
     * Amount the vendor still owes the platform for this order (0 for void / platform-collected orders).
     */
    public function amountDue(Order $order): float
    {
        return OrderProfitCalculator::vendorOwnPlatformForOrder($order);
    }

    /**
     * Preview the payment breakdown for an order given a chosen gateway.
     * The platform share (platform_profit) includes the order's shipping charge: shipping_cost is the part of
     * the amount due that is that charge, product_cost is the rest (cost + fixed shipping - central discounts + tax).
     *
     * @return array{amount:float,product_cost:float,shipping_cost:float,subtotal:float,gateway_fee:float,total:float}
     */
    public function previewBreakdown(Order $order, ?CentralGateway $gateway): array
    {
        $due = round($this->amountDue($order), 2);
        $gatewayFee = ($gateway && $due > 0) ? $this->gatewayFee($gateway, $due) : 0.0;
        $shipping = round(min(max((float) $order->shipping_charge, 0.0), $due), 2);

        return [
            'amount' => $due,
            'product_cost' => round($due - $shipping, 2),
            'shipping_cost' => $shipping,
            'subtotal' => $due,
            'gateway_fee' => $gatewayFee,
            'total' => round($due + $gatewayFee, 2),
        ];
    }

    /**
     * Record a vendor -> platform payment of the order's remaining amount (or $principal when given).
     */
    public function settle(Order $order, CentralGateway $gateway, float $gatewayFee, string $reference, ?float $principal = null): Order
    {
        OrderPayoutLedger::recordVendorSettlement(
            $order,
            $principal,
            $gatewayFee,
            trim($reference),
            $gateway->exists ? (int) $gateway->id : null,
        );

        return $order->fresh();
    }

    /**
     * Undo a settlement (admin correction use-case): deletes the Settlement Payments records.
     */
    public function unsettle(Order $order): Order
    {
        OrderPayoutLedger::clearVendorSettlements($order);

        return $order->fresh();
    }
}
