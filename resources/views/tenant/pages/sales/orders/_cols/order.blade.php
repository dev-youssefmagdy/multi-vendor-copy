<div class="entity-title">{{ $order->uuid }}</div>
<div class="entity-subtitle">{{ $order->items_count }} items · {{ $order->items->sum('qty') }} units</div>
