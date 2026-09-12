@php
    $gatewayName = str((string) $order->payment_method)->replace(['_', '-'], ' ')->headline()->toString() ?: ($order->paymentGateway?->name ?? 'Unknown');
@endphp
<div class="entity-title">{{ $gatewayName ?: '-' }}</div>
<div class="entity-subtitle">{{ $order->payment_method ?: 'No method' }}</div>
