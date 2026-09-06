{{--
  $flash_sales — Collection<FlashSale>
    ->products                Collection<Product>
    ->discount_percentage     float

  Product:
    ->slug, ->translationValue('name'), ->primary_image_url
    ->storefrontPricing() → ['current_price','original_price','has_discount','discount_percentage']

  $currentCurrency->symbol, ->conversion_rate
--}}
@php
    $flashSale = $flash_sales->first();
    $products = $flashSale?->products ?? collect();
    $sym = $currentCurrency?->symbol ?? '$';
    $rate = (float) ($currentCurrency?->conversion_rate ?? 1.0);
@endphp

@if ($flashSale && $products->isNotEmpty())
<section class="flash-sale">
    <h2>Flash Sale — {{ (int) $flashSale->discount_percentage }}% OFF</h2>

    <div class="product-grid">
        @foreach ($products as $product)
            @php $pricing = $product->storefrontPricing(); @endphp
            <div class="product-card">
                <a href="{{ route('tenant.storefront.product', $product->slug) }}">
                    @if ($product->primary_image_url)
                        <img src="{{ $product->primary_image_url }}" alt="{{ $product->translationValue('name') }}">
                    @endif
                    <h3>{{ $product->translationValue('name') ?? $product->slug }}</h3>
                    <span>{{ $sym }}{{ number_format($pricing['current_price'] * $rate, 2) }}</span>
                    @if ($pricing['has_discount'])
                        <s>{{ $sym }}{{ number_format($pricing['original_price'] * $rate, 2) }}</s>
                    @endif
                </a>

                @livewire('storefront.add-to-cart-button', ['product' => $product->id], key('flash-' . $product->id))
            </div>
        @endforeach
    </div>
</section>
@endif
