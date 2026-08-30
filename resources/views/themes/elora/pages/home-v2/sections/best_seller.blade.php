    @php
      $bestSellerProducts = [
        ['image' => 'assets/images/product-hoodie.png', 'badge' => 'Best Seller', 'badgeBg' => 'var(--color-primary)', 'name' => 'Essential Hoodie', 'weight' => '200g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => null],
        ['image' => 'assets/images/product-sneaker.png', 'badge' => 'Best Seller', 'badgeBg' => 'var(--color-primary)', 'name' => 'Essential Shoes', 'weight' => '250g', 'desc' => 'Premium cotton blend', 'rating' => '4.6 (+1.4k)', 'price' => '$110.00', 'oldPrice' => '$130.00', 'discount' => '15% Off'],
        ['image' => 'assets/images/flash-pants.png', 'badge' => 'Best Seller', 'badgeBg' => 'var(--color-primary)', 'name' => 'Classic Pants', 'weight' => '300g', 'desc' => 'Premium cotton blend', 'rating' => '4.4 (+960)', 'price' => '$79.00', 'oldPrice' => '$99.00', 'discount' => '20% Off'],
        ['image' => 'assets/images/product-hoodie.png', 'badge' => 'Best Seller', 'badgeBg' => 'var(--color-primary)', 'name' => 'Fleece Hoodie', 'weight' => '220g', 'desc' => 'Premium cotton blend', 'rating' => '4.3 (+710)', 'price' => '$99.00', 'oldPrice' => '$120.00', 'discount' => '18% Off'],
        ['image' => 'assets/images/product-sneaker.png', 'badge' => 'Best Seller', 'badgeBg' => 'var(--color-primary)', 'name' => 'Runner Sneakers', 'weight' => '260g', 'desc' => 'Premium cotton blend', 'rating' => '4.6 (+1.2k)', 'price' => '$110.00', 'oldPrice' => null, 'discount' => null],
        ['image' => 'assets/images/flash-pants.png', 'badge' => 'Best Seller', 'badgeBg' => 'var(--color-primary)', 'name' => 'Tailored Pants', 'weight' => '310g', 'desc' => 'Premium cotton blend', 'rating' => '4.1 (+430)', 'price' => '$84.00', 'oldPrice' => null, 'discount' => null],
      ];
    @endphp
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] flex flex-col gap-[16px] lg:gap-[34px] overflow-hidden"
      style="background: var(--color-yellow-bright)"
    >
      <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
          Best Seller
        </h2>
        <a
          href="#"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-accent-purple)"
          >see all</a
        >
      </div>
      <div class="swiper best-seller-swiper">
        <div class="swiper-wrapper" id="bestSellerWrapper">
          @foreach ($bestSellerProducts as $product)
            <div class="swiper-slide best-seller-slide">
              @include('themes.elora.pages.home-v2.sections.partials.product_card', ['p' => $product])
            </div>
          @endforeach
        </div>
      </div>
    </section>
