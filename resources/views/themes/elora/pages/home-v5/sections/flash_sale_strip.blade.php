    @if ($flashProducts->isNotEmpty())
    @php
      $currency = $currentCurrency ?? null;
      $symbol = data_get($currency, 'symbol', '$');
      $rate = (float) data_get($currency, 'conversion_rate', 1.0);

      $flashSaleProducts = $flashProducts->take(6)->map(function ($product) use ($symbol, $rate) {
          $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
          $pricing = $product->storefrontPricing($variant);
          $hasDiscount = (bool) $pricing['has_discount'];
          $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? asset('elora-5/assets/images/product-placeholder.svg');
          $rating = (float) ($product->average_rating ?? 0);
          $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

          return [
              'id' => $product->id,
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
              'weight' => $product->centralProduct?->category?->name ?? '',
              'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : '',
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : '',
              'stock' => '',
          ];
      })->values();
    @endphp
    <!-- ============ FLASH SALE ============ -->
    <section
      class="relative overflow-hidden px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col items-center gap-[20px]"
      style="
        background: linear-gradient(
          120deg,
          var(--color-flash-rainbow-pink) 0%,
          var(--color-flash-rainbow-orange) 25%,
          var(--color-flash-rainbow-yellow) 50%,
          var(--color-flash-rainbow-teal) 75%,
          var(--color-flash-rainbow-purple) 100%
        );
      "
    >
      <div class="relative flex items-center gap-[10px]">
        <img
          src="{{ asset('elora-5/assets/icons/icon-flash.svg') }}"
          alt=""
          class="size-[28px] lg:size-[40px]"
        />
        <h2
          class="font-semibold text-[26px] lg:text-[42px] text-white tracking-[0.5px]"
        >
          Flash Sale
        </h2>
        <img
          src="{{ asset('elora-5/assets/icons/icon-flash.svg') }}"
          alt=""
          class="size-[28px] lg:size-[40px]"
        />
      </div>
      <div class="relative w-full">
        <div class="swiper card-swiper">
          <div class="swiper-wrapper" id="flashSaleWrapper">
            @if ($flashSaleProducts->get(0))
              <div class="swiper-slide !w-[200px] lg:!w-[298px] !h-[302px] lg:!h-[450px]" wire:key="flash-v5-{{ $flashSaleProducts[0]['id'] }}">
                @include('themes.elora.pages.home-v5.sections.partials.fan_card', ['p' => $flashSaleProducts[0]])
              </div>
            @endif
            @if ($flashSaleProducts->get(1) || $flashSaleProducts->get(2))
              <div class="swiper-slide !w-[362px] lg:!w-[536px] !h-[302px] lg:!h-[450px]">
                <div class="flex flex-col gap-[10px] lg:gap-[12px] items-start h-full">
                  <div class="flex items-center gap-[6px] lg:gap-[8px]" @if($flashBanner?->end_date ?? null) data-countdown="{{ $flashBanner->end_date->timestamp }}" @endif>
                    <span class="font-semibold text-[12px] lg:text-[16px] tracking-[0.6px] -rotate-90 w-0 whitespace-nowrap" style="color:var(--color-yellow)">Ends in</span>
                    <div class="flex items-center justify-center h-[36px] w-[38px] lg:h-[52px] lg:w-[54px] rounded-[8px]" style="background:linear-gradient(180deg, #FFFFFF 51%, #E5E5E5 51%)">
                      <span class="font-semibold text-[18px] lg:text-[26px]" style="color:var(--color-price-blue)" data-flash-timer>03</span>
                    </div>
                    <div class="flex items-center justify-center h-[36px] w-[38px] lg:h-[52px] lg:w-[54px] rounded-[8px]" style="background:linear-gradient(180deg, #FFFFFF 51%, #E5E5E5 51%)">
                      <span class="font-semibold text-[18px] lg:text-[26px]" style="color:var(--color-price-blue)" data-flash-timer>06</span>
                    </div>
                    <div class="flex items-center justify-center h-[36px] w-[38px] lg:h-[52px] lg:w-[54px] rounded-[8px]" style="background:linear-gradient(180deg, #FFFFFF 51%, #E5E5E5 51%)">
                      <span class="font-semibold text-[18px] lg:text-[26px]" style="color:var(--color-price-blue)" data-flash-timer>25</span>
                    </div>
                  </div>
                  @if ($flashSaleProducts->get(1))
                    @include('themes.elora.pages.home-v5.sections.partials.flash_mini_card', ['p' => $flashSaleProducts[1]])
                  @endif
                  @if ($flashSaleProducts->get(2))
                    @include('themes.elora.pages.home-v5.sections.partials.flash_mini_card', ['p' => $flashSaleProducts[2]])
                  @endif
                </div>
              </div>
            @endif
            @foreach ($flashSaleProducts->slice(3, 3) as $p)
              <div class="swiper-slide !w-[200px] lg:!w-[298px] !h-[302px] lg:!h-[450px]" wire:key="flash-v5-{{ $p['id'] }}">
                @include('themes.elora.pages.home-v5.sections.partials.fan_card', ['p' => $p])
              </div>
            @endforeach
          </div>
        </div>
        <button
          id="flashSalePrev"
          type="button"
          aria-label="Previous"
          class="swiper-nav-btn swiper-nav-prev"
        >
          <img
            src="{{ asset('elora-5/assets/icons/arrow-down.svg') }}"
            class="size-[14px] rotate-90"
            alt=""
          />
        </button>
        <button
          id="flashSaleNext"
          type="button"
          aria-label="Next"
          class="swiper-nav-btn swiper-nav-next"
        >
          <img
            src="{{ asset('elora-5/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90"
            alt=""
          />
        </button>
      </div>
      <a
        href="{{ route('tenant.storefront.best-selling') }}"
        class="relative border border-white rounded-full h-[48px] lg:h-[64px] px-[32px] flex items-center justify-center cursor-pointer"
      >
        <span
          class="font-medium text-white text-[16px] lg:text-[20px] tracking-[0.5px]"
          >{{ __('Shop now') }}</span
        >
      </a>
    </section>
    @endif
