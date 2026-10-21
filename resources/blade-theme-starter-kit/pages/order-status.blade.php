@extends('layout.app')
@section('title', 'Order Status — ' . ($storeName ?? ''))

@section('content')
<div class="wrap" style="padding:32px 0">
    <h1>Order #{{ $order->uuid }}</h1>
    <p>Status: <strong>{{ $order->status?->value ?? $order->status }}</strong></p>
    <p>Total: {{ $currentCurrency?->symbol ?? '$' }}{{ number_format($order->grand_total, 2) }}</p>
    @isset($paymentState)
        <p>Payment: {{ $paymentState->label() }}</p>
    @endisset

    {{--
        Optional after-sales partials shipped by the platform (safe to remove or restyle).
        They read the optional variables $cancellation, $refunds, $paymentState, $canCancel,
        $cancelDecision, $cancelReasons and $returnItems and fall back gracefully without them.
    --}}
    {{-- Red "cancelled" banner (reason, note, date, who) or violet "refunded" banner --}}
    @include('livewire.tenant.storefront.partials.order-cancellation-summary')
    {{-- Refund cards (reference, amount, method, status, dates) --}}
    @include('livewire.tenant.storefront.partials.order-refunds-summary')

    <h2 style="margin-top:24px">Items</h2>
    @foreach ($order->items as $item)
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #eee">
            <span>
                {{ $item->product?->translationValue('name') ?? 'Product' }} × {{ $item->qty }}
                ({{ $currentCurrency?->symbol ?? '$' }}{{ number_format($item->price, 2) }} each{{ (float) $item->fixed_shipping > 0 ? ', fixed shipping ' . number_format($item->fixed_shipping, 2) : '' }}{{ (float) $item->discount > 0 ? ', discount -' . number_format($item->discount, 2) : '' }})
                {{-- Optional: "Request Return" / return status / "Return after delivery" --}}
                @include('livewire.tenant.storefront.partials.return-item-action')
            </span>
            <span>{{ $currentCurrency?->symbol ?? '$' }}{{ number_format($item->grand_total, 2) }}</span>
        </div>
    @endforeach

    @php $sym = $currentCurrency?->symbol ?? '$'; @endphp
    <div style="max-width:360px;margin-top:12px">
        <div style="display:flex;justify-content:space-between"><span>Items subtotal</span><span>{{ $sym }}{{ number_format($order->subtotal, 2) }}</span></div>
        @if ($order->items_fixed_shipping > 0)
            <div style="display:flex;justify-content:space-between"><span>Fixed shipping</span><span>{{ $sym }}{{ number_format($order->items_fixed_shipping, 2) }}</span></div>
        @endif
        @if ($order->items_discount > 0)
            <div style="display:flex;justify-content:space-between"><span>Flash-sale discount</span><span>-{{ $sym }}{{ number_format($order->items_discount, 2) }}</span></div>
        @endif
        @if ($order->items_tax > 0)
            <div style="display:flex;justify-content:space-between"><span>Tax</span><span>{{ $sym }}{{ number_format($order->items_tax, 2) }}</span></div>
        @endif
        <div style="display:flex;justify-content:space-between"><span>Shipping</span><span>{{ $sym }}{{ number_format($order->resolved_shipping_charge, 2) }}</span></div>
        @if ($order->discount_amount > 0)
            <div style="display:flex;justify-content:space-between"><span>Coupon discount</span><span>-{{ $sym }}{{ number_format($order->discount_amount, 2) }}</span></div>
        @endif
        <div style="display:flex;justify-content:space-between;font-weight:bold"><span>Grand total</span><span>{{ $sym }}{{ number_format($order->grand_total, 2) }}</span></div>
    </div>

    <a href="{{ route('tenant.storefront.order-tracking', $order->uuid) }}" style="display:inline-block;margin-top:20px">Track this order →</a>

    {{-- Optional: "Cancel order" button + reason/confirmation modal (or the shipped notice) --}}
    <div style="margin-top:20px;max-width:360px">
        @include('livewire.tenant.storefront.partials.order-cancel-action')
    </div>
</div>
@endsection
