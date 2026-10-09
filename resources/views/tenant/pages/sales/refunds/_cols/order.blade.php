@if($orderId)
    <a class="entity-title" href="{{ route('tenant.orders.show', $orderId) }}" title="{{ $orderNumber }}">{{ \Illuminate\Support\Str::limit($orderNumber, 13) }}</a>
@else
    <span class="entity-subtitle" title="{{ $orderNumber }}">{{ \Illuminate\Support\Str::limit($orderNumber, 13) }}</span>
@endif
