@php
    $profit = \App\Support\OrderProfitCalculator::vendorProfitForOrder($order);
@endphp
<div class="entity-title">${{ number_format((float) $order->grand_total, 2) }}</div>
<div class="entity-subtitle">{{ __('Shipping $:resolvedshippingcharge · Your profit $:profit', ['resolvedshippingcharge' => number_format((float) $order->resolved_shipping_charge, 2), 'profit' => number_format($profit, 2)]) }}</div>
