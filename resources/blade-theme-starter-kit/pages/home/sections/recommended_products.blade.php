{{-- $recommended_products — Collection<Product> --}}
@if ($recommended_products->isNotEmpty())
<section class="recommended">
    <h2>Recommended For You</h2>
    <div class="product-grid">
        @foreach ($recommended_products as $product)
            <div class="product-card">
                <a href="{{ route('tenant.storefront.product', $product->slug) }}">
                    @if ($product->primary_image_url)
                        <img src="{{ $product->primary_image_url }}" alt="{{ $product->translationValue('name') }}">
                    @endif
                    <h3>{{ $product->translationValue('name') ?? $product->slug }}</h3>
                    <span>{{ $currentCurrency?->symbol ?? '$' }}{{ number_format($product->storefrontPricing()['current_price'] * ($currentCurrency?->conversion_rate ?? 1.0), 2) }}</span>
                </a>
                @livewire('storefront.add-to-cart-button', ['product' => $product->id], key('rec-' . $product->id))
            </div>
        @endforeach
    </div>
</section>
@endif
