@extends('layout.app')
@section('title', ($category?->translationValue('name') ?? 'Shop') . ' — ' . ($storeName ?? ''))

@section('content')
<div class="wrap" style="padding:32px 0">
    <h1>{{ $category?->translationValue('name') ?? 'All Products' }}</h1>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:24px">
        @foreach ($products as $product)
            @php $pricing = $product->storefrontPricing(); @endphp
            <a href="{{ route('tenant.storefront.product', $product->slug) }}" style="border:1px solid #eee;border-radius:12px;padding:12px;display:block">
                @if ($product->primary_image_url)<img src="{{ $product->primary_image_url }}" style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:8px">@endif
                <p style="margin-top:8px">{{ $product->translationValue('name') ?? $product->slug }}</p>
                <strong>{{ $currentCurrency?->symbol ?? '$' }}{{ number_format($pricing['current_price'] * ($currentCurrency?->conversion_rate ?? 1), 2) }}</strong>
            </a>
        @endforeach
    </div>

    {{ $products->links() }}
</div>
@endsection
