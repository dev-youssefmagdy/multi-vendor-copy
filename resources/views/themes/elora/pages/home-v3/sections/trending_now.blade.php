    @php
      $PLACEHOLDER_IMG = asset('elora-3/assets/images/product-placeholder.svg');
      $trendingProducts = [
        ['image' => $PLACEHOLDER_IMG, 'badge' => '70% Sold', 'badgeBg' => 'var(--color-accent-yellow)', 'badgeText' => 'var(--color-text-primary)', 'name' => 'Essential Hoodie', 'weight' => '200g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'progress' => 83, 'progressLabel' => '5 ordered last 30 min', 'urgency' => 'var(--color-success)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => '70% Sold', 'badgeBg' => 'var(--color-accent-yellow)', 'badgeText' => 'var(--color-text-primary)', 'name' => 'Essential Hoodie', 'weight' => '200g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'progress' => 65, 'progressLabel' => '4 ordered last 30 min', 'urgency' => 'var(--color-error)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => '70% Sold', 'badgeBg' => 'var(--color-accent-yellow)', 'badgeText' => 'var(--color-text-primary)', 'name' => 'Essential Hoodie', 'weight' => '200g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'progress' => 45, 'progressLabel' => '3 ordered last 30 min', 'urgency' => 'var(--color-success)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => '70% Sold', 'badgeBg' => 'var(--color-accent-yellow)', 'badgeText' => 'var(--color-text-primary)', 'name' => 'Essential Hoodie', 'weight' => '200g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'progress' => 72, 'progressLabel' => '5 ordered last 30 min', 'urgency' => 'var(--color-error)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => '70% Sold', 'badgeBg' => 'var(--color-accent-yellow)', 'badgeText' => 'var(--color-text-primary)', 'name' => 'Essential Hoodie', 'weight' => '200g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'progress' => 58, 'progressLabel' => '3 ordered last 30 min', 'urgency' => 'var(--color-success)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => '70% Sold', 'badgeBg' => 'var(--color-accent-yellow)', 'badgeText' => 'var(--color-text-primary)', 'name' => 'Essential Hoodie', 'weight' => '200g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'progress' => 90, 'progressLabel' => '6 ordered last 30 min', 'urgency' => 'var(--color-error)'],
      ];
    @endphp
    <section
      class="px-[16px] lg:px-[56px] py-[48px] flex flex-col gap-[16px] lg:gap-[24px] bg-[#F0F0F0]"
    >
      <div class="flex items-center justify-between">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-text-primary)"
        >
          Trending Now
        </h2>
        <a
          href="#"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-success)"
          >see all</a
        >
      </div>
      <div class="relative">
        <div class="swiper card-swiper" id="trendingSwiper">
          <div class="swiper-wrapper">
            @foreach ($trendingProducts as $p)
              <div class="swiper-slide h-auto !w-[210px] lg:!w-[260px]">
                @include('themes.elora.pages.home-v3.sections.partials.product_card', ['p' => $p])
              </div>
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
            src="{{ asset('elora-3/assets/icons/arrow-down.svg') }}"
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
            src="{{ asset('elora-3/assets/icons/arrow-down.svg') }}"
            class="size-[14px] -rotate-90"
            alt=""
          />
        </button>
      </div>
    </section>
