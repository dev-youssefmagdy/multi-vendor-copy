@extends('layout.app')
@section('title', ($product->translationValue('name') ?? $product->slug) . ' — ' . ($storeName ?? ''))

@section('content')
<div class="wrap" style="padding:32px 0">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px">
        <div>
            @if ($product->primary_image_url)
                <img src="{{ $product->primary_image_url }}" alt="{{ $product->translationValue('name') }}" style="width:100%;border-radius:12px">
            @endif
        </div>
        <div>
            <h1>{{ $product->translationValue('name') ?? $product->slug }}</h1>

            <div style="margin:16px 0">
                <span style="font-size:28px;font-weight:800">{{ $symbol }}{{ number_format($sellPrice * $rate, 2) }}</span>
                @if ($hasDiscount)
                    <s style="color:#999;margin-left:10px">{{ $symbol }}{{ number_format($realPrice * $rate, 2) }}</s>
                    <span style="color:#ef4444;font-weight:700;margin-left:6px">-{{ (int) $discountPct }}%</span>
                @endif
            </div>

            @if ($avgRating)
                <p>⭐ {{ number_format($avgRating, 1) }} ({{ $reviewCount }} reviews)</p>
            @endif

            <p style="color:{{ $isInStock ? 'green' : 'red' }}">
                {{ $isInStock ? 'In Stock' : 'Out of Stock' }}
            </p>

            @if ($variants->count() > 1)
                <div style="margin:16px 0">
                    <label>Options</label>
                    <select id="variant-select">
                        @foreach ($variants as $v)
                            <option value="{{ $v->id }}" {{ $activeVariant?->id === $v->id ? 'selected' : '' }}>
                                {{ $v->name ?? 'Variant #' . $v->id }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div style="display:flex;align-items:center;gap:12px;margin:20px 0">
                <input type="number" id="qty-input" value="{{ $qty }}" min="1" style="width:64px;padding:8px">
                <button type="button" id="add-to-cart-btn"
                    data-product-id="{{ $product->id }}"
                    data-variant-id="{{ $activeVariant?->id }}"
                    data-cart-add-url="{{ $cartAddUrl }}"
                    style="flex:1;padding:12px;background:#111;color:#fff;border:none;border-radius:8px;font-weight:700">
                    Add to Cart
                </button>
            </div>

            <div>{!! $product->translationValue('description') !!}</div>
        </div>
    </div>

    @if ($related->isNotEmpty())
    <section style="margin-top:60px">
        <h2>You May Also Like</h2>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:16px">
            @foreach ($related as $p)
                @php $pr = $p->storefrontPricing(); @endphp
                <a href="{{ route('tenant.storefront.product', $p->slug) }}" style="border:1px solid #eee;border-radius:12px;padding:12px;display:block">
                    @if ($p->primary_image_url)<img src="{{ $p->primary_image_url }}" style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:8px">@endif
                    <p style="margin-top:8px">{{ $p->translationValue('name') ?? $p->slug }}</p>
                    <strong>{{ $symbol }}{{ number_format($pr['current_price'] * $rate, 2) }}</strong>
                </a>
            @endforeach
        </div>
    </section>
    @endif
</div>

<script>
document.getElementById('add-to-cart-btn')?.addEventListener('click', async function () {
    const btn = this;
    const variantSelect = document.getElementById('variant-select');
    const qty = parseInt(document.getElementById('qty-input').value, 10) || 1;
    const token = document.querySelector('meta[name="csrf-token"]').content;

    const res = await fetch(btn.dataset.cartAddUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({
            product_id: parseInt(btn.dataset.productId, 10),
            variant_id: variantSelect ? parseInt(variantSelect.value, 10) : (btn.dataset.variantId ? parseInt(btn.dataset.variantId, 10) : null),
            qty,
        }),
    });
    const data = await res.json();
    if (data.success && window.trackAddToCart) {
        window.trackAddToCart({ content_ids: [btn.dataset.productId], num_items: qty });
    }
    alert(data.success ? 'Added to cart!' : (data.message || 'Could not add to cart.'));
});
</script>
@endsection
