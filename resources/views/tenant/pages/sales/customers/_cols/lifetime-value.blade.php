@php
    $lifetime = (float) $customer->orders->sum(fn ($order) => $order->grand_total);
    $avg = $customer->orders_count > 0 ? $lifetime / $customer->orders_count : 0.0;
@endphp
${{ number_format($lifetime, 2) }}
<div class="entity-subtitle">Avg ${{ number_format($avg, 2) }}</div>
