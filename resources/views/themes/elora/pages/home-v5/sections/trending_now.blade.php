    @php
      $trendingProducts = [
        ['name' => 'Essential Hoodie', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'stock' => 'Only 5 left'],
        ['name' => 'Essential Hoodie', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'stock' => 'Only 5 left'],
        ['name' => 'Retro Sneakers', 'weight' => '260g', 'price' => '$99.00', 'oldPrice' => '$120.00', 'discount' => '18% Off', 'rating' => '4.4 (+610)', 'stock' => 'Only 6 left'],
        ['name' => 'Studio Headphones', 'weight' => '190g', 'price' => '$99.00', 'oldPrice' => '$119.00', 'discount' => '17% Off', 'rating' => '4.3 (+700)', 'stock' => 'Only 3 left'],
        ['name' => 'Court Sneakers', 'weight' => '270g', 'price' => '$92.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.3 (+390)', 'stock' => 'Only 4 left'],
        ['name' => 'Wireless Mouse', 'weight' => '120g', 'price' => '$39.00', 'oldPrice' => '$49.00', 'discount' => '20% Off', 'rating' => '4.1 (+430)', 'stock' => 'Only 8 left'],
      ];
    @endphp
    <!-- ============ TRENDING NOW ============ -->
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] flex flex-col gap-[16px] lg:gap-[28px]"
      style="background: var(--color-page-bg)"
    >
      <div class="flex items-center justify-between">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-black)"
        >
          Trending Now
        </h2>
        <a
          href="#"
          class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-primary)"
          >see all</a
        >
      </div>
      <div class="relative">
        <div class="swiper card-swiper trending-swiper">
          <div class="swiper-wrapper" id="trendingWrapper">
            @foreach ($trendingProducts as $p)
              <div class="swiper-slide">
                @include('themes.elora.pages.home-v5.sections.partials.wide_card', ['p' => $p])
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </section>
