    @php
      $trendingProducts = [
        ['image' => 'assets/images/product-sneaker.png', 'name' => 'Essential Shoes', 'weight' => '250g', 'badge' => '70% Sold', 'badgeBg' => 'var(--color-accent-yellow)', 'badgeText' => 'var(--color-black)', 'progress' => 83, 'ordered' => '5 ordered last 30 min', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off'],
        ['image' => 'assets/images/product-hoodie.png', 'name' => 'Essential Hoodie', 'weight' => '200g', 'badge' => 'Best Seller', 'badgeBg' => 'var(--color-primary)', 'badgeText' => 'var(--color-white)', 'progress' => 65, 'ordered' => '3 ordered last 30 min', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => null, 'discount' => null],
        ['image' => 'assets/images/flash-pants.png', 'name' => 'Classic Pants', 'weight' => '300g', 'badge' => 'New', 'badgeBg' => 'var(--color-accent-purple)', 'badgeText' => 'var(--color-white)', 'progress' => 45, 'ordered' => '2 ordered last 30 min', 'rating' => '4.1 (+430)', 'price' => '$79.00', 'oldPrice' => '$99.00', 'discount' => '20% Off'],
      ];
    @endphp
    <section
      class="px-[16px] lg:px-[56px] py-12 flex flex-col gap-[16px] lg:gap-[34px]"
      style="background: var(--color-page-bg)"
    >
      <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
          Trending Now
        </h2>
        <a
          href="#"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-accent-purple)"
          >see all</a
        >
      </div>
      <div class="relative">
        <div class="swiper card-swiper">
          <div class="swiper-wrapper" id="trendingWrapper">
            @foreach ($trendingProducts as $product)
              @include('themes.elora.pages.home-v2.sections.partials.trending_card', ['p' => $product])
            @endforeach
          </div>
        </div>
        <button
          id="trendingPrev"
          type="button"
          aria-label="Previous"
          class="swiper-nav-btn swiper-nav-prev"
        >
          <img
            src="{{ asset('elora-1/assets/icons/arrow-down.svg') }}"
            class="size-[14px] rotate-90"
            alt=""
          />
        </button>
        <button
          id="trendingNext"
          type="button"
          aria-label="Next"
          class="swiper-nav-btn swiper-nav-next"
        >
          <img
            src="{{ asset('elora-1/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90"
            alt=""
          />
        </button>
      </div>
    </section>
