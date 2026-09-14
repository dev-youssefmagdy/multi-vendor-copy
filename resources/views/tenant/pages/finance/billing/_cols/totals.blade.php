@php
    $profit = \App\Support\OrderProfitCalculator::effectiveTenantProfitForOrder($order);
@endphp
<div class="entity-title">${{ number_format((float) $order->grand_total, 2) }}</div>
<div class="entity-subtitle">Ship ${{ number_format((float) $order->resolved_shipping_charge, 2) }} · Profit ${{ number_format($profit, 2) }}</div>
