@extends('layout.app')
@section('title', 'Cart — ' . ($storeName ?? ''))

@section('content')
<div class="wrap" style="padding:32px 0">
    <h1>Your Cart</h1>

    @if (empty($cartItems))
        <p>Your cart is empty. <a href="/">Continue shopping</a></p>
    @else
        <div style="margin-top:24px">
            @foreach ($cartItems as $key => $item)
                <div style="display:flex;align-items:center;gap:16px;padding:16px 0;border-bottom:1px solid #eee">
                    @if ($item['product']->primary_image_url ?? null)
                        <img src="{{ $item['product']->primary_image_url }}" style="width:80px;height:80px;object-fit:cover;border-radius:8px">
                    @endif
                    <div style="flex:1">
                        <p>{{ $item['product']->translationValue('name') ?? '' }}</p>
                        <input type="number" value="{{ $item['qty'] }}" min="1" class="qty-input" data-key="{{ $key }}" style="width:60px">
                    </div>
                    <strong>{{ $currentCurrency?->symbol ?? '$' }}{{ number_format($item['subtotal'], 2) }}</strong>
                    <button type="button" class="remove-btn" data-key="{{ $key }}">Remove</button>
                </div>
            @endforeach
        </div>

        <div style="margin-top:24px;text-align:right">
            <p>Subtotal: {{ $currentCurrency?->symbol ?? '$' }}{{ number_format($cartTotal, 2) }}</p>
            @if ($cartDiscount > 0)<p>Discount: -{{ $currentCurrency?->symbol ?? '$' }}{{ number_format($cartDiscount, 2) }}</p>@endif
            <p><strong>Total: {{ $currentCurrency?->symbol ?? '$' }}{{ number_format($cartFinalTotal, 2) }}</strong></p>
            <a href="{{ route('tenant.storefront.checkout') }}" style="display:inline-block;padding:12px 32px;background:#111;color:#fff;border-radius:8px;margin-top:12px">Checkout</a>
        </div>
    @endif
</div>

<script>
const token = document.querySelector('meta[name="csrf-token"]').content;

document.querySelectorAll('.remove-btn').forEach(btn => btn.addEventListener('click', async function () {
    await fetch('/cart/remove', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ key: this.dataset.key }),
    });
    location.reload();
}));

document.querySelectorAll('.qty-input').forEach(input => input.addEventListener('change', async function () {
    await fetch('/cart/update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ key: this.dataset.key, qty: parseInt(this.value, 10) }),
    });
    location.reload();
}));
</script>
@endsection
