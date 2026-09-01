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
<section class="relative overflow-hidden px-[16px] lg:px-[56px] py-[24px] lg:py-[42px] flex flex-col items-center gap-[16px] lg:gap-[24px]" style="background: var(--color-souqify-teal)">
  <img src="{{ asset('souqify-2/assets/images/best-seller-texture-mobile.png') }}" alt="" class="lg:hidden absolute inset-0 h-full w-full object-cover opacity-30 mix-blend-hard-light pointer-events-none" />
  <img src="{{ asset('souqify-2/assets/images/best-seller-texture-desktop.png') }}" alt="" class="hidden lg:block absolute inset-0 h-full w-full object-cover opacity-30 mix-blend-hard-light pointer-events-none" />
  <h2 class="relative font-semibold text-[24px] lg:text-[40px] text-white">{{ __('Best Seller') }}</h2>
  @if ($__bestSellerCards->isNotEmpty())
    <div class="relative w-full">
      <div class="swiper card-swiper">
        <div class="swiper-wrapper" id="bestSellerWrapper">
          @foreach ($__bestSellerCards as $p)
            <div class="swiper-slide h-auto !w-[170px] lg:!w-[250px]" wire:key="best-seller-v3-{{ $p['id'] }}">
              @include('themes.souqify.pages.home-v2.sections.partials.product_card', ['p' => $p])
            </div>
          @endforeach
        </div>
      </div>
      <button id="bestSellerPrev" type="button" aria-label="{{ __('Previous') }}" class="swiper-nav-btn swiper-nav-prev"><img src="{{ asset('souqify-2/assets/icons/icon-chevron-down-dark.svg') }}" class="size-[14px] rotate-90" alt="" /></button>
      <button id="bestSellerNext" type="button" aria-label="{{ __('Next') }}" class="swiper-nav-btn swiper-nav-next"><img src="{{ asset('souqify-2/assets/icons/icon-chevron-down-dark.svg') }}" class="size-[14px] -rotate-90" alt="" /></button>
    </div>
    <a href="{{ route('tenant.storefront.best-selling') }}" class="relative border border-white rounded-full h-[38px] lg:h-[48px] px-[24px] lg:px-[32px] flex items-center justify-center cursor-pointer">
      <span class="font-medium text-white text-[14px] lg:text-[16px] tracking-[0.5px]">{{ __('Explore all') }}</span>
    </a>
  @else
    <p class="relative text-white/80 text-sm py-6">{{ __('No best sellers yet.') }}</p>
  @endif
</section>
