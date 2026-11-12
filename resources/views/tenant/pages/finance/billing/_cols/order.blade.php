<div class="entity-title">{{ $order->uuid }}</div>
<div class="entity-subtitle">{{ trans_choice(':count item|:count items', $order->items_count, ['count' => $order->items_count]) }} · {{ $order->created_at?->format('M d, Y') }}</div>
