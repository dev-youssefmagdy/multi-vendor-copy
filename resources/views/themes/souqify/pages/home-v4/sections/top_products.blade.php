@php
    $__bestSellerCards = collect($bestSelling ?? [])->map(function ($product) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

        return [
            'id' => $product->id,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'badge' => __('Best Seller'),
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
        ];
    });
@endphp
<!-- ============ BEST SELLER ============ -->
<section class="wave-surface px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] flex flex-col gap-[16px] lg:gap-[28px]">
  <img src="{{ asset('souqify-3/assets/images/best-seller-swirl-bg.png') }}" alt="" class="wave-overlay" />
  <div class="relative flex items-center justify-between">
    <h2 class="font-extrabold text-white text-[26px] lg:text-[40px]">{{ __('Best Seller') }}</h2>
    <a href="{{ route('tenant.storefront.best-selling') }}" class="text-white text-[14px] lg:text-[20px] tracking-[0.5px]">{{ __('see all') }}</a>
  </div>
  @if ($__bestSellerCards->isNotEmpty())
    <div class="relative">
      <div class="swiper card-swiper">
        <div class="swiper-wrapper" id="bestSellerWrapper">
          @foreach ($__bestSellerCards as $p)
            <div class="swiper-slide h-auto !w-[160px] lg:!w-[210px]" wire:key="best-seller-v4-{{ $p['id'] }}">
              @include('themes.souqify.pages.home-v2.sections.partials.product_card', ['p' => $p])
            </div>
          @endforeach
        </div>
      </div>
    </div>
    <div class="best-seller-pagination relative"></div>
  @else
    <p class="relative text-white/80 text-sm py-6">{{ __('No best sellers yet.') }}</p>
  @endif
</section>
