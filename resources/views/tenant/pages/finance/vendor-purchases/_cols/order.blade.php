<div class="entity-title">{{ $order->uuid }}</div>
<div class="entity-subtitle">{{ __(':itemscount items · :createdat', ['itemscount' => $order->items_count, 'createdat' => $order->created_at?->format('M d, Y')]) }}</div>
