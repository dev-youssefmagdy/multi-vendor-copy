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
<section class="bestseller-bg px-[16px] lg:px-[56px] py-[24px] flex flex-col gap-[16px] lg:gap-[24px]">
  <div class="flex items-center justify-between">
    <h2 class="font-extrabold text-[26px] lg:text-[40px]" style="color:var(--color-text-primary)">{{ __('Best Seller') }}</h2>
    <a href="{{ route('tenant.storefront.best-selling') }}" class="text-[14px] lg:text-[20px] tracking-[0.5px]" style="color:var(--color-text-primary)">{{ __('see all') }}</a>
  </div>
  @if ($__bestSellerCards->isNotEmpty())
    <div class="relative">
      <div class="swiper card-swiper">
        <div class="swiper-wrapper" id="bestSellerWrapper">
          @foreach ($__bestSellerCards as $p)
            <div class="swiper-slide h-auto !w-[210px] lg:!w-[242px]" wire:key="best-seller-v2-{{ $p['id'] }}">
              @include('themes.souqify.pages.home-v2.sections.partials.product_card', ['p' => $p])
            </div>
          @endforeach
        </div>
      </div>
      <button id="bestSellerPrev" type="button" aria-label="{{ __('Previous') }}" class="swiper-nav-btn swiper-nav-prev"><img src="{{ asset('souqify-1/assets/icons/icon-chevron-down-dark.svg') }}" class="size-[14px] rotate-90" alt="" /></button>
      <button id="bestSellerNext" type="button" aria-label="{{ __('Next') }}" class="swiper-nav-btn swiper-nav-next"><img src="{{ asset('souqify-1/assets/icons/icon-chevron-down-dark.svg') }}" class="size-[14px] -rotate-90" alt="" /></button>
    </div>
    <div class="dot-row">
      <span class="dot is-active"></span>
      <span class="dot"></span>
      <span class="dot"></span>
    </div>
  @else
    <p class="text-sm py-6" style="color:var(--color-text-muted)">{{ __('No best sellers yet.') }}</p>
  @endif
</section>
