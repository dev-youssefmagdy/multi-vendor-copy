<div class="entity-title">{{ number_format((float) $order->grand_total, 2) }}</div>
<div class="entity-subtitle">
    @if((float) $order->discount_percentage > 0)
        {{ number_format((float) $order->discount_percentage, 1) }}% off ·
    @endif
    ship {{ number_format((float) $order->resolved_shipping_charge, 2) }}
</div>
