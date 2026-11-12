@php
    $gatewayName = __((string) str((string) $order->payment_method)->replace(['_', '-'], ' ')->headline()) ?: ($order->paymentGateway?->name ?? __('Unknown'));
@endphp
<div class="entity-title">{{ $gatewayName ?: '-' }}</div>
<div class="entity-subtitle">{{ $order->payment_method ?: __('No method') }}</div>
