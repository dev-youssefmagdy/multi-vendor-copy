@if ($order->vendor_gateway_id !== null)
    ${{ number_format((float) $order->vendor_gateway_fee, 2) }}
@else
    <span class="entity-subtitle">&mdash;</span>
@endif
