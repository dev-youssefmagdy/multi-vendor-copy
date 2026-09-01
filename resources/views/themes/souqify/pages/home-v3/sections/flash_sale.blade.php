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
<section class="relative overflow-hidden px-[16px] lg:px-[56px] py-[24px] lg:py-[42px]" style="background: var(--color-souqify-teal)" @if($__flashEnd) data-flash-end="{{ $__flashEnd->timestamp }}" @endif>
  <img src="{{ asset('souqify-2/assets/images/flash-sale-texture-mobile.png') }}" alt="" class="lg:hidden absolute inset-0 h-full w-full object-cover mix-blend-soft-light pointer-events-none" />
  <img src="{{ asset('souqify-2/assets/images/flash-sale-texture-desktop.png') }}" alt="" class="hidden lg:block absolute inset-0 h-full w-full object-cover mix-blend-soft-light pointer-events-none" />
  <div class="relative flex flex-col gap-[16px] lg:gap-[24px]">
    <!-- Mobile: heading + horizontal countdown stacked above the carousel -->
    <div class="flex flex-col lg:hidden items-center gap-[12px]">
      <h2 class="font-extrabold text-[32px] text-white text-center">{{ __('Flash Sale') }}</h2>
      <div class="flex items-center gap-[8px] justify-center">
        <span class="font-semibold text-[12px] tracking-[0.5px]" style="color: var(--color-accent-yellow)">{{ __('Ends in') }}</span>
        <div class="flex items-center justify-center h-[44px] w-[46px] rounded-[7px] border border-white" style="background: var(--color-souqify-teal)">
          <span data-flash-hours class="font-semibold text-[24px] text-white">03</span>
        </div>
        <div class="flex items-center justify-center h-[44px] w-[46px] rounded-[7px] border border-white" style="background: var(--color-souqify-teal)">
          <span data-flash-minutes class="font-semibold text-[24px] text-white">06</span>
        </div>
        <div class="flex items-center justify-center h-[44px] w-[46px] rounded-[7px] border border-white" style="background: var(--color-souqify-teal)">
          <span data-flash-seconds class="font-semibold text-[24px] text-white">25</span>
        </div>
      </div>
    </div>

    @if ($__flashCards->isNotEmpty())
      <div class="hidden lg:flex items-stretch gap-[24px]">
        <!-- Desktop: vertical "Flash Sale" heading + vertically-stacked countdown, fixed left column -->
        <div class="flex items-center justify-center gap-[24px] shrink-0">
          <div class="flex items-center justify-center w-[64px] h-[290px]">
            <h2 class="whitespace-nowrap font-extrabold text-[56px] text-white" style="transform: rotate(-90deg)">{{ __('Flash Sale') }}</h2>
          </div>
          <div class="flex flex-col items-center gap-2">
            <div class="flex flex-col items-center gap-[10px]">
              <div class="flex items-center justify-center h-[58px] w-[58px] rounded-[7px] border border-white" style="background: var(--color-souqify-teal)">
                <span data-flash-seconds class="font-semibold text-[30px] text-white">25</span>
              </div>
              <div class="flex items-center justify-center h-[58px] w-[58px] rounded-[7px] border border-white" style="background: var(--color-souqify-teal)">
                <span data-flash-minutes class="font-semibold text-[30px] text-white">06</span>
              </div>
              <div class="flex items-center justify-center h-[58px] w-[58px] rounded-[7px] border border-white" style="background: var(--color-souqify-teal)">
                <span data-flash-hours class="font-semibold text-[30px] text-white">03</span>
              </div>
            </div>
            <div class="flex items-center justify-center w-[20px]">
              <span class="whitespace-nowrap font-semibold text-[16px] tracking-[0.5px]" style="color: var(--color-accent-yellow); transform: rotate(180deg);">{{ __('Ends in') }}</span>
            </div>
          </div>
        </div>
        <div class="relative flex-1 min-w-0">
          <div class="swiper card-swiper">
            <div class="swiper-wrapper" id="flashSaleWrapperDesktop">
              @foreach ($__flashCards as $p)
                <div class="swiper-slide h-auto !w-[210px] lg:!w-[242px]" wire:key="flash-v3-desktop-{{ $p['id'] }}">
                  @include('themes.souqify.pages.home-v2.sections.partials.product_card', ['p' => $p])
                </div>
              @endforeach
            </div>
          </div>
          <button id="flashSalePrevDesktop" type="button" aria-label="{{ __('Previous') }}" class="swiper-nav-btn swiper-nav-prev"><img src="{{ asset('souqify-2/assets/icons/icon-chevron-down-dark.svg') }}" class="size-[14px] rotate-90" alt="" /></button>
          <button id="flashSaleNextDesktop" type="button" aria-label="{{ __('Next') }}" class="swiper-nav-btn swiper-nav-next"><img src="{{ asset('souqify-2/assets/icons/icon-chevron-down-dark.svg') }}" class="size-[14px] -rotate-90" alt="" /></button>
        </div>
      </div>

      <!-- Mobile: single-row carousel -->
      <div class="relative lg:hidden">
        <div class="swiper card-swiper">
          <div class="swiper-wrapper" id="flashSaleWrapper">
            @foreach ($__flashCards as $p)
              <div class="swiper-slide h-auto !w-[210px] lg:!w-[242px]" wire:key="flash-v3-{{ $p['id'] }}">
                @include('themes.souqify.pages.home-v2.sections.partials.product_card', ['p' => $p])
              </div>
            @endforeach
          </div>
        </div>
        <button id="flashSalePrev" type="button" aria-label="{{ __('Previous') }}" class="swiper-nav-btn swiper-nav-prev"><img src="{{ asset('souqify-2/assets/icons/icon-chevron-down-dark.svg') }}" class="size-[14px] rotate-90" alt="" /></button>
        <button id="flashSaleNext" type="button" aria-label="{{ __('Next') }}" class="swiper-nav-btn swiper-nav-next"><img src="{{ asset('souqify-2/assets/icons/icon-chevron-down-dark.svg') }}" class="size-[14px] -rotate-90" alt="" /></button>
      </div>
      <div class="flex items-center justify-center">
        <a href="{{ route('tenant.storefront.category') }}?section=flash_sale" class="bg-white flex h-[48px] lg:h-[56px] items-center justify-center rounded-full w-full max-w-[370px] px-[8px] cursor-pointer">
          <span class="font-medium text-[16px] lg:text-[20px] tracking-[0.5px]" style="color: var(--color-souqify-teal)">{{ __('Shop now') }}</span>
        </a>
      </div>
    @else
      <p class="text-white/80 text-sm py-6 text-center">{{ __('Flash deals coming soon') }}</p>
    @endif
  </div>
</section>
