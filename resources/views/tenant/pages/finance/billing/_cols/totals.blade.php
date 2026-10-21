@php
    $profit = \App\Support\OrderProfitCalculator::vendorProfitForOrder($order);
@endphp
<div class="entity-title">${{ number_format((float) $order->grand_total, 2) }}</div>
<div class="entity-subtitle">Shipping ${{ number_format((float) $order->resolved_shipping_charge, 2) }} · Your profit ${{ number_format($profit, 2) }}</div>
