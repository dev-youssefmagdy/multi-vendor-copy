    @php
      $bsBadge = ['badge' => '30% OFF', 'badgeBg' => 'var(--color-primary)', 'badgeColor' => 'var(--color-white)', 'weightColor' => 'var(--color-primary)', 'deliveredColor' => 'var(--color-error)'];
      $bestSellerProducts = [
        ['image' => 'images/product-placeholder.svg', 'name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'] + $bsBadge,
        ['image' => 'images/product-placeholder.svg', 'name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'] + $bsBadge,
        ['image' => 'images/product-placeholder.svg', 'name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'] + $bsBadge,
        ['image' => 'images/product-placeholder.svg', 'name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'] + $bsBadge,
        ['image' => 'images/product-placeholder.svg', 'name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'] + $bsBadge,
        ['image' => 'images/product-placeholder.svg', 'name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'] + $bsBadge,
        ['image' => 'images/product-placeholder.svg', 'name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'] + $bsBadge,
        ['image' => 'images/product-placeholder.svg', 'name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'] + $bsBadge,
        ['image' => 'images/product-placeholder.svg', 'name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'] + $bsBadge,
        ['image' => 'images/product-placeholder.svg', 'name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'] + $bsBadge,
        ['image' => 'images/product-placeholder.svg', 'name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'] + $bsBadge,
        ['image' => 'images/product-placeholder.svg', 'name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'] + $bsBadge,
        ['image' => 'images/product-placeholder.svg', 'name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'] + $bsBadge,
        ['image' => 'images/product-placeholder.svg', 'name' => 'Weekend Backpack', 'weight' => '480g', 'price' => '$89.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.2 (+850)', 'weightColor' => 'var(--color-primary)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Essential Shoes', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'weightColor' => 'var(--color-primary)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Essential Shoes', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'weightColor' => 'var(--color-primary)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.2 (+850)'] + $bsBadge,
        ['image' => 'images/product-placeholder.svg', 'name' => 'Weekend Backpack', 'weight' => '480g', 'price' => '$99.00', 'oldPrice' => '$120.00', 'discount' => '18% Off', 'rating' => '4.3 (+710)', 'weightColor' => 'var(--color-primary)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Trail Sneakers', 'weight' => '270g', 'price' => '$115.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.4 (+560)', 'weightColor' => 'var(--color-primary)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Smart Watch', 'weight' => '60g', 'price' => '$129.00', 'oldPrice' => '$159.00', 'discount' => '19% Off', 'rating' => '4.6 (+1.1k)', 'weightColor' => 'var(--color-primary)'],
      ];
      $bestSellerGroups = array_chunk($bestSellerProducts, 4);
    @endphp
    <!-- ============ BEST SELLER ============ -->
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col gap-[16px] lg:gap-[24px]"
      style="background: var(--color-accent-yellow)"
    >
      <div class="flex items-center justify-between">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-text-primary)"
        >
          Best Seller
        </h2>
        <a
          href="#"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-text-secondary-dark)"
          >see all</a
        >
      </div>
      <div class="relative">
        <div class="swiper card-swiper w-full">
          <div class="swiper-wrapper" id="bestSellerWrapper">
            @foreach ($bestSellerGroups as $group)
              @php [$full, $small1, $small2, $wide] = $group; @endphp
              <div class="swiper-slide h-auto">
                <div class="flex gap-[12px] h-full">
                  <div class="flex-1">
                    @include('themes.elora.pages.home-v6.sections.partials.product_card', ['p' => $full])
                  </div>
                  <div class="flex-1 flex flex-col gap-[12px]">
                    <div class="flex gap-[12px] flex-1">
                      @include('themes.elora.pages.home-v6.sections.partials.best_seller_small_card', ['p' => $small1])
                      @include('themes.elora.pages.home-v6.sections.partials.best_seller_small_card', ['p' => $small2])
                    </div>
                    @include('themes.elora.pages.home-v6.sections.partials.best_seller_wide_card', ['p' => $wide])
                  </div>
                </div>
              </div>
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
            src="assets/icons/arrow-down.svg"
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
            src="assets/icons/arrow-down.svg"
            class="size-[14px] -rotate-90"
            alt=""
          />
        </button>
      </div>
    </section>
