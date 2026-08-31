    @php
      $flashSaleProducts = ($flashProducts ?? collect())->map(fn ($product) => $product->toEloraV4Card($currentCurrency ?? null));
      $flashSaleMinPrice = ($flashProducts ?? collect())->map(fn ($product) => $product->storefrontPricing()['current_price'])->filter()->min();
      $flashSaleMinPriceLabel = $flashSaleMinPrice !== null
        ? data_get($currentCurrency ?? null, 'symbol', '$') . number_format($flashSaleMinPrice * (float) data_get($currentCurrency ?? null, 'conversion_rate', 1.0), 2)
        : null;
      $flashSaleDiscountPct = ($flashSales ?? collect())->max('discount_percentage');
    @endphp
    @if ($flashSaleProducts->isNotEmpty())
    <!-- ============ FLASH SALE ============ -->
    <section
      class="texture-bg texture-hard px-[16px] lg:px-[56px] py-[24px] lg:py-[16px] flex flex-col items-center gap-[24px]"
    >
      <img
        src="{{ asset('elora-4/assets/images/flash-sale-texture.png') }}"
        alt=""
        class="texture-overlay"
      />
      <div
        class="relative flex flex-col lg:flex-row items-center gap-[16px] lg:gap-[20px] w-full"
      >
        <div class="relative flex flex-col items-start gap-[16px] shrink-0">
          <div class="relative">
            <img
              src="{{ asset('elora-4/assets/images/flash-sale-illustration.png') }}"
              alt="{{ __('Flash Sale') }}"
              class="w-[180px] lg:w-[283px] h-auto"
            />
            <div
              class="absolute top-[6px] left-[4px] lg:top-[14px] lg:left-[10px] flex items-center justify-center h-[38px] w-[80px] lg:h-[62px] lg:w-[130px] rounded-full border-2 border-white"
              style="background: var(--color-brand-orange)"
            >
              <span
                class="font-normal text-[14px] lg:text-[22px] text-white tracking-[0.5px]"
                >{{ $flashSaleDiscountPct ? round($flashSaleDiscountPct) . '% ' . __('Off') : __('Flash Sale') }}</span
              >
            </div>
          </div>
          <!-- timer wrapper -->
          <div class="flex items-center gap-[8px]">
            <span
              class="-rotate-90 w-auto text-[13px] lg:text-[20px] text-white tracking-[0.8px] w-0 whitespace-nowrap"
              >Ends in</span
            >
            <div class="flex items-center gap-[8px] translate-x-[-20px]">
              <div
                class="flex items-center justify-center h-[48px] w-[50px] lg:h-[73px] lg:w-[76px] rounded-[11px] border-2 border-white"
                style="background: var(--color-brand-orange-bright)"
              >
                <span
                  class="font-semibold text-[26px] lg:text-[40px] text-white"
                  data-flash-timer
                  >03</span
                >
              </div>
              <div
                class="flex items-center justify-center h-[48px] w-[50px] lg:h-[73px] lg:w-[76px] rounded-[11px] border-2 border-white"
                style="background: var(--color-brand-orange-bright)"
              >
                <span
                  class="font-semibold text-[26px] lg:text-[40px] text-white"
                  data-flash-timer
                  >06</span
                >
              </div>
              <div
                class="flex items-center justify-center h-[48px] w-[50px] lg:h-[73px] lg:w-[76px] rounded-[11px] border-2 border-white"
                style="background: var(--color-brand-orange-bright)"
              >
                <span
                  class="font-semibold text-[26px] lg:text-[40px] text-white"
                  data-flash-timer
                  >25</span
                >
              </div>
            </div>
          </div>
        </div>

        <!-- swiper -->
        <div class="relative flex-1 min-w-0 w-full">
          <div class="swiper card-swiper flash-sale-grid">
            <div class="swiper-wrapper" id="flashSaleWrapper">
              @foreach ($flashSaleProducts as $p)
                @include('themes.elora.pages.home-v4.sections.partials.flash_card', ['p' => $p])
              @endforeach
            </div>
          </div>

          <div
            class="absolute bottom-[-25px] z-50 -left-[50px] -translate-x-1/2 bg-white border-2 border-black rounded-full h-[42px] lg:h-[48px] flex items-center justify-center px-[20px] lg:px-[24px]"
          >
            <p
              class="font-bold text-[14px] lg:text-[21px] whitespace-nowrap"
              style="color: var(--color-brand-orange)"
            >
              {{ $flashSaleMinPriceLabel ? __('Only :price', ['price' => $flashSaleMinPriceLabel]) : __('Flash deals') }}
            </p>
          </div>
        </div>
      </div>
      <div class="relative flex flex-col items-center pb-[8px]">
        <a
          href="{{ route('tenant.storefront.category') }}"
          class="border border-white rounded-full h-[48px] lg:h-[64px] px-[32px] flex items-center justify-center cursor-pointer"
          style="background: var(--color-brand-orange)"
        >
          <span
            class="font-medium text-white text-[16px] lg:text-[20px] tracking-[0.5px]"
            >{{ __('Explore all') }}</span
          >
        </a>
      </div>
    </section>
    @endif
