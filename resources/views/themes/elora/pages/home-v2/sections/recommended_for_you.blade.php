    @php
      $recommendedProducts = [
        ['image' => 'assets/images/product-sneaker.png', 'badge' => 'For You', 'badgeBg' => 'var(--color-accent-purple)', 'name' => 'Court Sneakers', 'weight' => '255g', 'desc' => 'Premium cotton blend', 'rating' => '4.3 (+390)', 'price' => '$92.00', 'oldPrice' => null, 'discount' => null],
        ['image' => 'assets/images/product-hoodie.png', 'badge' => 'Sale', 'badgeBg' => 'var(--color-primary)', 'name' => 'Oversized Hoodie', 'weight' => '230g', 'desc' => 'Premium cotton blend', 'rating' => '4.5 (+980)', 'price' => '$88.00', 'oldPrice' => '$105.00', 'discount' => '16% Off'],
        ['image' => 'assets/images/flash-pants.png', 'badge' => 'New', 'badgeBg' => 'var(--color-accent-purple)', 'name' => 'Relaxed Pants', 'weight' => '295g', 'desc' => 'Premium cotton blend', 'rating' => '4.0 (+210)', 'price' => '$76.00', 'oldPrice' => null, 'discount' => null],
        ['image' => 'assets/images/product-hoodie.png', 'badge' => 'For You', 'badgeBg' => 'var(--color-accent-purple)', 'name' => 'Zip Hoodie', 'weight' => '215g', 'desc' => 'Premium cotton blend', 'rating' => '4.4 (+640)', 'price' => '$97.00', 'oldPrice' => '$115.00', 'discount' => '15% Off'],
        ['image' => 'assets/images/product-sneaker.png', 'badge' => 'Sale', 'badgeBg' => 'var(--color-primary)', 'name' => 'Street Sneakers', 'weight' => '265g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+505)', 'price' => '$105.00', 'oldPrice' => '$125.00', 'discount' => '16% Off'],
        ['image' => 'assets/images/flash-pants.png', 'badge' => 'New', 'badgeBg' => 'var(--color-accent-purple)', 'name' => 'Cargo Pants', 'weight' => '320g', 'desc' => 'Premium cotton blend', 'rating' => '4.1 (+330)', 'price' => '$82.00', 'oldPrice' => null, 'discount' => null],
        ['image' => 'assets/images/product-hoodie.png', 'badge' => 'For You', 'badgeBg' => 'var(--color-accent-purple)', 'name' => 'Pullover Hoodie', 'weight' => '225g', 'desc' => 'Premium cotton blend', 'rating' => '4.6 (+890)', 'price' => '$91.00', 'oldPrice' => null, 'discount' => null],
        ['image' => 'assets/images/product-sneaker.png', 'badge' => 'Sale', 'badgeBg' => 'var(--color-primary)', 'name' => 'Classic Sneakers', 'weight' => '250g', 'desc' => 'Premium cotton blend', 'rating' => '4.3 (+700)', 'price' => '$99.00', 'oldPrice' => '$119.00', 'discount' => '17% Off'],
      ];
    @endphp
    <section
      class="px-[16px] lg:px-[56px] py-[24px] flex flex-col gap-[16px] lg:gap-[34px]"
    >
      <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
          Recommended For You
        </h2>
        <a
          href="#"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-accent-purple)"
          >see all</a
        >
      </div>
      <div
        id="recommendedWrapper"
        class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-[12px] lg:gap-[16px]"
      >
        @foreach ($recommendedProducts as $product)
          <div class="h-[380px] lg:h-[440px]">
            @include('themes.elora.pages.home-v2.sections.partials.product_card', ['p' => $product])
          </div>
        @endforeach
      </div>
    </section>
