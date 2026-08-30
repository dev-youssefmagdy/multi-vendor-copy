    @php
      $trendingImg = asset('elora-4/assets/images/product-placeholder.svg');
      $trendingProducts = [
        ['image' => $trendingImg, 'name' => 'Essential Shoes', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'ordered' => '5 ordered last 30 min', 'progress' => 83],
        ['image' => $trendingImg, 'name' => 'Essential Shoes', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'ordered' => '5 ordered last 30 min', 'progress' => 83],
        ['image' => $trendingImg, 'name' => 'Essential Shoes', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'ordered' => '5 ordered last 30 min', 'progress' => 83],
      ];
    @endphp
    <!-- ============ TRENDING NOW ============ -->
    <section
      class="pattern-trending px-[16px] lg:px-[56px] py-[24px] flex flex-col gap-[16px] lg:gap-[34px]"
    >
      <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
          Trending Now
        </h2>
        <a
          href="#"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-brand-orange-bright)"
          >see all</a
        >
      </div>
      <div class="relative">
        <div class="swiper card-swiper trending-swiper">
          <div class="swiper-wrapper" id="trendingWrapper">
            @foreach ($trendingProducts as $p)
              @include('themes.elora.pages.home-v4.sections.partials.trending_card', ['p' => $p])
            @endforeach
          </div>
        </div>
      </div>
    </section>
