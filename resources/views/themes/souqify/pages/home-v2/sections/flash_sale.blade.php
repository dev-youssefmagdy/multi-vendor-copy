@php
    $__flashEnd = optional($flashSales ?? collect())->first()?->end_at;
    $__flashCards = collect($flashProducts ?? [])->map(function ($product) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

        return [
            'id' => $product->id,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'badge' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
        ];
    });
@endphp
<!-- ============ FLASH SALE ============ -->
<section class="stripe-bg-dark px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col gap-[16px] lg:gap-[24px]" @if($__flashEnd) data-flash-end="{{ $__flashEnd->timestamp }}" @endif>
  <div class="flex items-center justify-between">
    <h2 class="font-extrabold text-[26px] lg:text-[40px] text-white">{{ __("Today's Flash Sale") }}</h2>
    <a href="{{ route('tenant.storefront.category') }}?section=flash_sale" class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px] text-white">{{ __('see all') }}</a>
  </div>
  @if ($__flashCards->isNotEmpty())
    <div class="relative">
      <div class="swiper card-swiper">
        <div class="swiper-wrapper" id="flashSaleWrapper">
          @foreach ($__flashCards as $p)
            <div class="swiper-slide h-auto !w-[210px] lg:!w-[242px]">
              @include('themes.souqify.pages.home-v2.sections.partials.product_card', ['p' => $p])
            </div>
          @endforeach
        </div>
      </div>
      <button id="flashSalePrev" type="button" aria-label="{{ __('Previous') }}" class="swiper-nav-btn swiper-nav-prev"><img src="{{ asset('souqify-1/assets/icons/icon-chevron-down-dark.svg') }}" class="size-[14px] rotate-90" alt="" /></button>
      <button id="flashSaleNext" type="button" aria-label="{{ __('Next') }}" class="swiper-nav-btn swiper-nav-next"><img src="{{ asset('souqify-1/assets/icons/icon-chevron-down-dark.svg') }}" class="size-[14px] -rotate-90" alt="" /></button>
    </div>
    <div class="flex items-center gap-[12px] justify-end">
      <div class="flex items-center gap-[6px]">
        <span class="font-semibold text-[14px] lg:text-[18px] tracking-[0.5px]" style="color:var(--color-accent-yellow)">{{ __('Ends in') }}</span>
        <div class="flex items-center justify-center rounded-[10px] border-2 border-white h-[44px] lg:h-[68px] w-[46px] lg:w-[71px]" style="background:var(--color-brand-purple)">
          <span class="font-semibold text-[24px] lg:text-[37px] text-white tracking-[0.5px]" data-flash-timer="h">03</span>
        </div>
        <div class="flex items-center justify-center rounded-[10px] border-2 border-white h-[44px] lg:h-[68px] w-[46px] lg:w-[71px]" style="background:var(--color-brand-purple)">
          <span class="font-semibold text-[24px] lg:text-[37px] text-white tracking-[0.5px]" data-flash-timer="m">06</span>
        </div>
        <div class="flex items-center justify-center rounded-[10px] border-2 border-white h-[44px] lg:h-[68px] w-[46px] lg:w-[71px]" style="background:var(--color-brand-purple)">
          <span class="font-semibold text-[24px] lg:text-[37px] text-white tracking-[0.5px]" data-flash-timer="s">25</span>
        </div>
      </div>
      <a href="{{ route('tenant.storefront.category') }}?section=flash_sale" class="flex-1 lg:flex-none lg:w-[calc(100%-330px)] h-[44px] lg:h-[68px] rounded-[34px] bg-white flex items-center justify-center px-[24px] cursor-pointer">
        <span class="font-medium text-[16px] lg:text-[24px] tracking-[0.5px]" style="color:var(--color-brand-purple)">{{ __('Shop now') }}</span>
      </a>
    </div>
  @else
    <p class="text-white/80 text-sm py-6">{{ __('Flash deals coming soon') }}</p>
  @endif
</section>
