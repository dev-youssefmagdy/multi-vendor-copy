@if ($order->vendor_gateway_id !== null)
    ${{ number_format((float) $order->vendor_gateway_fee, 2) }}
    <div class="entity-subtitle">Pre-calculated</div>
@else
    <span class="entity-subtitle">&mdash;</span>
    <div class="entity-subtitle">Set on settle</div>
@endif
