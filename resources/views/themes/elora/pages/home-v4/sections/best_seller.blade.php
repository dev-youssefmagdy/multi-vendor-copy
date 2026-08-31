    @php
      $bestSellerProducts = ($bestSelling ?? collect())->map(fn ($product) => $product->toEloraV4Card($currentCurrency ?? null));
    @endphp
    @if ($bestSellerProducts->isNotEmpty())
    <!-- ============ BEST SELLER ============ -->
    <section
      class="texture-bg px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col items-center gap-[16px] lg:gap-[24px]"
      wire:ignore
    >
      <img
        src="{{ asset('elora-4/assets/images/best-seller-texture.png') }}"
        alt=""
        class="texture-overlay"
      />
      <h2 class="relative font-bold text-[24px] lg:text-[36px] text-white">
        {{ __('Best Seller') }}
      </h2>
      <div class="relative w-full">
        <div class="swiper card-swiper">
          <div class="swiper-wrapper" id="bestSellerWrapper">
            @foreach ($bestSellerProducts as $p)
              @include('themes.elora.pages.home-v4.sections.partials.best_seller_card', ['p' => $p])
            @endforeach
          </div>
        </div>
        <button
          id="bestSellerPrev"
          type="button"
          aria-label="Previous"
          class="swiper-nav-btn swiper-nav-prev"
        >
          <img
            src="{{ asset('elora-4/assets/icons/arrow-down.svg') }}"
            class="size-[14px] rotate-90"
            alt=""
          />
        </button>
        <button
          id="bestSellerNext"
          type="button"
          aria-label="Next"
          class="swiper-nav-btn swiper-nav-next"
        >
          <img
            src="{{ asset('elora-4/assets/icons/arrow-down.svg') }}"
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
          >{{ __('Explore all') }}</span
        >
      </a>
    </section>
    @endif
