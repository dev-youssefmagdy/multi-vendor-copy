    @php
      $flashSaleProducts = [
        ['name' => 'Essential Hoodie', 'weight' => '200g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'stock' => 'Only 5 left'],
        ['name' => 'Essential Shoes', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'stock' => 'Only 5 left'],
        ['name' => 'Wireless Headphones', 'weight' => '180g', 'price' => '$79.00', 'oldPrice' => '$99.00', 'discount' => '20% Off', 'rating' => '4.4 (+560)', 'stock' => 'Only 3 left'],
        ['name' => 'Classic Sneakers', 'weight' => '260g', 'price' => '$110.00', 'oldPrice' => '$130.00', 'discount' => '15% Off', 'rating' => '4.6 (+1.4k)', 'stock' => 'Only 5 left'],
        ['name' => 'Wireless Mouse', 'weight' => '120g', 'price' => '$39.00', 'oldPrice' => '$49.00', 'discount' => '20% Off', 'rating' => '4.1 (+430)', 'stock' => 'Only 8 left'],
        ['name' => 'Graphic Tee', 'weight' => '150g', 'price' => '$45.00', 'oldPrice' => '$55.00', 'discount' => '18% Off', 'rating' => '4.3 (+390)', 'stock' => 'Only 6 left'],
      ];
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
          src="assets/icons/icon-flash.svg"
          alt=""
          class="size-[28px] lg:size-[40px]"
        />
        <h2
          class="font-semibold text-[26px] lg:text-[42px] text-white tracking-[0.5px]"
        >
          Flash Sale
        </h2>
        <img
          src="assets/icons/icon-flash.svg"
          alt=""
          class="size-[28px] lg:size-[40px]"
        />
      </div>
      <div class="relative w-full">
        <div class="swiper card-swiper">
          <div class="swiper-wrapper" id="flashSaleWrapper">
            <div class="swiper-slide !w-[200px] lg:!w-[298px] !h-[302px] lg:!h-[450px]">
              @include('themes.elora.pages.home-v5.sections.partials.fan_card', ['p' => $flashSaleProducts[0]])
            </div>
            <div class="swiper-slide !w-[362px] lg:!w-[536px] !h-[302px] lg:!h-[450px]">
              <div class="flex flex-col gap-[10px] lg:gap-[12px] items-start h-full">
                <div class="flex items-center gap-[6px] lg:gap-[8px]">
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
                @include('themes.elora.pages.home-v5.sections.partials.flash_mini_card', ['p' => $flashSaleProducts[1]])
                @include('themes.elora.pages.home-v5.sections.partials.flash_mini_card', ['p' => $flashSaleProducts[2]])
              </div>
            </div>
            @foreach ([$flashSaleProducts[3], $flashSaleProducts[4], $flashSaleProducts[5]] as $p)
              <div class="swiper-slide !w-[200px] lg:!w-[298px] !h-[302px] lg:!h-[450px]">
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
            src="assets/icons/arrow-down.svg"
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
            src="assets/icons/arrow-down.svg"
            class="size-[14px] -rotate-90"
            alt=""
          />
        </button>
      </div>
      <button
        type="button"
        class="relative border border-white rounded-full h-[48px] lg:h-[64px] px-[32px] flex items-center justify-center cursor-pointer"
      >
        <span
          class="font-medium text-white text-[16px] lg:text-[20px] tracking-[0.5px]"
          >Shop now</span
        >
      </button>
    </section>
