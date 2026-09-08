{{-- $best_sellers — Collection<Product> --}}
@if ($best_sellers->isNotEmpty())
@php $sym = $currentCurrency?->symbol ?? '$'; $rate = (float) ($currentCurrency?->conversion_rate ?? 1.0); @endphp
<section class="best-sellers">
    <h2>Best Sellers</h2>
    <a href="{{ route('tenant.storefront.best-selling') }}">View All</a>
    <div class="product-grid">
        @foreach ($best_sellers as $product)
            @php $pricing = $product->storefrontPricing(); @endphp
            <div class="product-card">
                <a href="{{ route('tenant.storefront.product', $product->slug) }}">
                    @if ($product->primary_image_url)
                        <img src="{{ $product->primary_image_url }}" alt="{{ $product->translationValue('name') }}">
                    @endif
                    <h3>{{ $product->translationValue('name') ?? $product->slug }}</h3>
                    <span>{{ $sym }}{{ number_format($pricing['current_price'] * $rate, 2) }}</span>
                </a>
                @livewire('storefront.add-to-cart-button', ['product' => $product->id], key('best-' . $product->id))
            </div>
        @endforeach
    </div>
</section>
@endif
