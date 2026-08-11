<?php

namespace App\Services\Tenant;

use App\Models\PaymentGateway as CentralGateway;
use App\Models\Tenant\Order;
use App\Support\OrderProfitCalculator;

/**
 * Handles the calculation and settlement of vendor purchases from central.
 *
 * Flow:
 *   1. When a customer order is paid the vendor owes central:
 *      product cost (vendor_cost) + shipping (shipping_charge).
 *   2. The vendor picks a central payment gateway to use.
 *   3. The gateway's transaction fee is calculated and added.
 *   4. Vendor submits payment reference → order is marked settled.
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
     * Preview the purchase breakdown for an order given a chosen gateway.
     *
     * @return array{product_cost:float,shipping_cost:float,subtotal:float,gateway_fee:float,total:float}
     */
    public function previewBreakdown(Order $order, ?CentralGateway $gateway): array
    {
        $productCost = round(OrderProfitCalculator::tenantOwnCentralForOrder($order), 2);
        $centralProductCost = round((float) ($order->central_cost ?? 0), 2);
        $shippingCost = round((float) ($order->shipping_charge ?? 0), 2);
        $subtotal = round($productCost + $shippingCost, 2);
        $gatewayFee = $gateway ? $this->gatewayFee($gateway, $subtotal) : 0.0;

        return [
            'product_cost' => $productCost,
            'shipping_cost' => $shippingCost,
            'subtotal' => $subtotal,
            'gateway_fee' => $gatewayFee,
            'total' => round($subtotal + $gatewayFee, 2),
        ];
    }

    /**
     * Mark the vendor purchase for an order as settled.
     */
    public function settle(Order $order, CentralGateway $gateway, float $gatewayFee, string $reference): Order
    {
        $order->forceFill([
            'vendor_gateway_id' => $gateway->id,
            'vendor_gateway_fee' => $gatewayFee,
            'vendor_settled_at' => now(),
            'vendor_settlement_ref' => trim($reference),
        ])->save();

        return $order->fresh();
    }

    /**
     * Undo a settlement (admin correction use-case).
     */
    public function unsettle(Order $order): Order
    {
        $order->forceFill([
            'vendor_gateway_id' => null,
            'vendor_gateway_fee' => 0,
            'vendor_settled_at' => null,
            'vendor_settlement_ref' => null,
        ])->save();

        return $order->fresh();
    }
}
