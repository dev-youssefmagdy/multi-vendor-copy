    @php
      $flashSaleProducts = [
        ['image' => asset('elora-3/assets/images/product-placeholder.svg'), 'name' => 'Ribbed Cami Dress', 'price' => '$7.49', 'oldPrice' => '$29.99', 'discount' => '-75%'],
        ['image' => asset('elora-3/assets/images/product-placeholder.svg'), 'name' => 'Oversized Graphic Tee', 'price' => '$5.99', 'oldPrice' => '$22.99', 'discount' => '-74%'],
        ['image' => asset('elora-3/assets/images/product-placeholder.svg'), 'name' => 'Wide Leg Linen Pants', 'price' => '$9.49', 'oldPrice' => '$39.99', 'discount' => '-76%'],
        ['image' => asset('elora-3/assets/images/product-placeholder.svg'), 'name' => 'Y2K Cargo Skirt', 'price' => '$8.99', 'oldPrice' => '$34.99', 'discount' => '-74%'],
      ];
    @endphp
    <section
      class="pattern-flash-sale px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col gap-[16px] lg:gap-[24px]"
    >
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-[8px] lg:gap-[8px]">
          <img
            src="{{ asset('elora-3/assets/icons/icon-flash.svg') }}"
            alt=""
            class="size-[20px] lg:size-[40px]"
          />
          <h2 class="font-semibold text-[20px] lg:text-[32px] text-white">
            Flash Sale
          </h2>
        </div>
        <div class="flex items-center gap-[8px] lg:gap-[14px]">
          <span
            class="font-semibold text-[13px] lg:text-[20px] tracking-[0.5px] lg:tracking-[0.83px]"
            style="color: var(--color-accent-yellow)"
            >Ends in</span
          >
          <div class="flex items-center gap-[6px] lg:gap-[8px]">
            <span
              id="flashHours"
              class="flex items-center justify-center font-semibold text-[16px] lg:text-[40px] text-white rounded-[8px] lg:rounded-[12px] h-[36px] lg:h-[73px] w-[36px] lg:w-[77px]"
              style="
                background: var(--color-brand-pink);
                border: 1.5px solid white;
              "
              >03</span
            >
            <span
              id="flashMinutes"
              class="flex items-center justify-center font-semibold text-[16px] lg:text-[40px] text-white rounded-[8px] lg:rounded-[12px] h-[36px] lg:h-[73px] w-[36px] lg:w-[77px]"
              style="
                background: var(--color-brand-pink);
                border: 1.5px solid white;
              "
              >06</span
            >
            <span
              id="flashSeconds"
              class="flex items-center justify-center font-semibold text-[16px] lg:text-[40px] text-white rounded-[8px] lg:rounded-[12px] h-[36px] lg:h-[73px] w-[36px] lg:w-[77px]"
              style="
                background: var(--color-brand-pink);
                border: 1.5px solid white;
              "
              >25</span
            >
          </div>
          <img
            src="{{ asset('elora-3/assets/icons/arrow-outlined.svg') }}"
            alt=""
            class="hidden lg:block h-[57px] w-auto"
          />
        </div>
      </div>
      <div class="relative">
        <div class="swiper card-swiper" id="flashSaleSwiper">
          <div class="swiper-wrapper">
            @foreach ($flashSaleProducts as $p)
              @include('themes.elora.pages.home-v3.sections.partials.flash_card', ['p' => $p])
            @endforeach
          </div>
        </div>
        <button
          id="flashSalePrev"
          type="button"
          aria-label="Previous"
          class="swiper-nav-btn swiper-nav-prev swiper-nav-btn-light"
        >
          <img
            src="{{ asset('elora-3/assets/icons/arrow-down.svg') }}"
            class="size-[14px] rotate-90"
            alt=""
          />
        </button>
        <button
          id="flashSaleNext"
          type="button"
          aria-label="Next"
          class="swiper-nav-btn swiper-nav-next swiper-nav-btn-light"
        >
          <img
            src="{{ asset('elora-3/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90"
            alt=""
          />
        </button>
      </div>
      <div class="flex items-center justify-center">
        <button
          type="button"
          class="border border-white rounded-full h-[44px] lg:h-[64px] px-[24px] lg:px-[32px] flex items-center justify-center cursor-pointer"
        >
          <span
            class="font-medium text-white text-[14px] lg:text-[20px] tracking-[0.5px]"
            >Explore all</span
          >
        </button>
      </div>
    </section>
