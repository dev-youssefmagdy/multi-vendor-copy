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
                {{-- Optional: "Request Return" / return status / "Return after delivery" --}}
                @include('livewire.tenant.storefront.partials.return-item-action')
            </span>
            <span>{{ $currentCurrency?->symbol ?? '$' }}{{ number_format($item->sub_total, 2) }}</span>
        </div>
    @endforeach

    <a href="{{ route('tenant.storefront.order-tracking', $order->uuid) }}" style="display:inline-block;margin-top:20px">Track this order →</a>

    {{-- Optional: "Cancel order" button + reason/confirmation modal (or the shipped notice) --}}
    <div style="margin-top:20px;max-width:360px">
        @include('livewire.tenant.storefront.partials.order-cancel-action')
    </div>
</div>
@endsection
