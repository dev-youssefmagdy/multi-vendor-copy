{{--
  $flash_sales — Collection<FlashSale>
    ->products                Collection<Product>
    ->discount_percentage     float

  Product:
    ->slug, ->translationValue('name'), ->primary_image_url
    ->storefrontPricing() → ['current_price','original_price','has_discount','discount_percentage']

  $currentCurrency->symbol, ->conversion_rate
--}}
@if ($flash_sales->isNotEmpty() && $flash_sales->first()->products->isNotEmpty())
<section class="flash-sale">
    <h2>Flash Sale — {{ (int) $flash_sales->first()->discount_percentage }}% OFF</h2>

    <div class="product-grid">
        @foreach ($flash_sales->first()->products as $product)
            <div class="product-card">
                <a href="{{ route('tenant.storefront.product', $product->slug) }}">
                    @if ($product->primary_image_url)
                        <img src="{{ $product->primary_image_url }}" alt="{{ $product->translationValue('name') }}">
                    @endif
                    <h3>{{ $product->translationValue('name') ?? $product->slug }}</h3>
                    <span>{{ $currentCurrency?->symbol ?? '$' }}{{ number_format($product->storefrontPricing()['current_price'] * ($currentCurrency?->conversion_rate ?? 1.0), 2) }}</span>
                    @if ($product->storefrontPricing()['has_discount'])
                        <s>{{ $currentCurrency?->symbol ?? '$' }}{{ number_format($product->storefrontPricing()['original_price'] * ($currentCurrency?->conversion_rate ?? 1.0), 2) }}</s>
                    @endif
                </a>
                @livewire('storefront.add-to-cart-button', ['product' => $product->id], key('flash-' . $product->id))
            </div>
        @endforeach
    </div>
</section>
@endif
