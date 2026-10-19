<div class="entity-title">{{ $order->uuid }}</div>
<div class="entity-subtitle">{{ $order->items_count }} {{ \Illuminate\Support\Str::plural('item', $order->items_count) }} · {{ $order->created_at?->format('M d, Y') }}</div>
