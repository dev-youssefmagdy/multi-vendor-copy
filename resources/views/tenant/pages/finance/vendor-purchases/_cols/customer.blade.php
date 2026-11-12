<div class="entity-title">{{ $order->customer?->full_name ?? __('Guest') }}</div>
<div class="entity-subtitle">{{ $order->customer?->email ?? '' }}</div>
