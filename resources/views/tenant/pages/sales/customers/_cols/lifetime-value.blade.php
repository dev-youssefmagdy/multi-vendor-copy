@php
    $lifetime = (float) $customer->orders->sum(fn ($order) => $order->grand_total);
    $avg = $customer->orders_count > 0 ? $lifetime / $customer->orders_count : 0.0;
@endphp
<span title="{{ __('Avg $:avg', ['avg' => number_format($avg, 2)]) }}">$ {{ number_format($lifetime, fmod($lifetime, 1.0) === 0.0 ? 0 : 2) }}</span>
