@extends('layout.app')
@section('title', 'Order Status — ' . ($storeName ?? ''))

@section('content')
<div class="wrap" style="padding:32px 0">
    <h1>Order #{{ $order->uuid }}</h1>
    <p>Status: <strong>{{ $order->status?->value ?? $order->status }}</strong></p>
    <p>Total: {{ $currentCurrency?->symbol ?? '$' }}{{ number_format($order->grand_total, 2) }}</p>

    <h2 style="margin-top:24px">Items</h2>
    @foreach ($order->items as $item)
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #eee">
            <span>{{ $item->product?->translationValue('name') ?? 'Product' }} × {{ $item->qty }}</span>
            <span>{{ $currentCurrency?->symbol ?? '$' }}{{ number_format($item->sub_total, 2) }}</span>
        </div>
    @endforeach

    <a href="{{ route('tenant.storefront.order-tracking', $order->uuid) }}" style="display:inline-block;margin-top:20px">Track this order →</a>
</div>
@endsection
