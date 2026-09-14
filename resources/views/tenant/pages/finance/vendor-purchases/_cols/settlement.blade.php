@if ($order->vendor_settled)
    <span class="badge badge-green">Settled</span>
    <div class="entity-subtitle">{{ optional($order->vendor_settled_at)->format('M d, Y') }}</div>
    @if ($order->vendor_settlement_ref)
        <div class="entity-subtitle" style="font-family:monospace;font-size:11px;">{{ str()->limit($order->vendor_settlement_ref, 24) }}</div>
    @endif
@else
    <span class="badge badge-amber">Pending</span>
@endif
