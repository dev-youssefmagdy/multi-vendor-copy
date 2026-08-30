    @php
      // Back-to-front stacking order (matches the fan: smallest/rightmost drawn first, largest/frontmost last)
      $bestSellerDesktop = [
        ['name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'alt' => true, 'stock' => 'Only 5 left'],
        ['name' => 'Essential Hoodie', 'weight' => '200g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'],
        ['name' => 'Essential Shoes', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'],
        ['name' => 'Essential Hoodie', 'weight' => '200g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'],
        ['name' => 'Essential Shoes', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'],
        ['name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'alt' => true, 'stock' => 'Only 5 left'],
      ];
      $bestSellerMobile = [
        ['name' => 'Essential Shoes', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'],
        ['name' => 'Essential Hoodie', 'weight' => '200g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'],
        ['name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'alt' => true, 'stock' => 'Only 5 left'],
      ];
    @endphp
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col items-center gap-[16px] lg:gap-[24px]"
      style="background: var(--color-yellow)"
    >
      <div class="flex items-center justify-between w-full">
        <h2 class="font-medium text-[22px] lg:text-[32px]" style="color: var(--color-black)">Best Seller</h2>
        <a href="#" class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]" style="color: var(--color-primary)">see all</a>
      </div>
      {{-- Mobile: 3-slot fan cascade --}}
      <div class="swiper bestseller-swiper lg:!hidden w-full">
        <div class="swiper-wrapper" id="bestSellerMobileWrapper">
          @foreach ($bestSellerMobile as $product)
            <div class="swiper-slide bestseller-slide" style="position:absolute; top:0; left:0;">
              @include('themes.elora.pages.home-v5.sections.partials.fan_card', ['p' => $product])
            </div>
          @endforeach
        </div>
      </div>
      {{-- Desktop: 6-slot fan cascade --}}
      <div class="swiper bestseller-swiper !hidden lg:!block w-full max-w-[1183px] mx-auto">
        <div class="swiper-wrapper" id="bestSellerDesktopWrapper">
          @foreach ($bestSellerDesktop as $product)
            <div class="swiper-slide bestseller-slide" style="position:absolute; top:0; left:0;">
              @include('themes.elora.pages.home-v5.sections.partials.fan_card', ['p' => $product])
            </div>
          @endforeach
        </div>
      </div>
    </section>
