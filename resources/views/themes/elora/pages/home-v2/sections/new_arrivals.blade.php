    @php
      $newInProducts = [
        ['image' => 'assets/images/product-hoodie.png', 'badge' => 'Best Seller', 'badgeBg' => 'var(--color-primary)', 'name' => 'Essential Hoodie', 'weight' => '200g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => null],
        ['image' => 'assets/images/product-sneaker.png', 'badge' => 'New', 'badgeBg' => 'var(--color-accent-purple)', 'name' => 'Essential Shoes', 'weight' => '250g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off'],
        ['image' => 'assets/images/flash-pants.png', 'badge' => 'Sale', 'badgeBg' => 'var(--color-primary)', 'name' => 'Classic Pants', 'weight' => '300g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$79.00', 'oldPrice' => '$99.00', 'discount' => '20% Off'],
        ['image' => 'assets/images/product-hoodie.png', 'badge' => 'New', 'badgeBg' => 'var(--color-accent-purple)', 'name' => 'Everyday Hoodie', 'weight' => '210g', 'desc' => 'Premium cotton blend', 'rating' => '4.5 (+620)', 'price' => '$95.00', 'oldPrice' => null, 'discount' => null],
        ['image' => 'assets/images/product-sneaker.png', 'badge' => 'Best Seller', 'badgeBg' => 'var(--color-primary)', 'name' => 'Runner Sneakers', 'weight' => '260g', 'desc' => 'Premium cotton blend', 'rating' => '4.6 (+1.2k)', 'price' => '$110.00', 'oldPrice' => '$130.00', 'discount' => '15% Off'],
        ['image' => 'assets/images/flash-pants.png', 'badge' => 'New', 'badgeBg' => 'var(--color-accent-purple)', 'name' => 'Tailored Pants', 'weight' => '310g', 'desc' => 'Premium cotton blend', 'rating' => '4.1 (+430)', 'price' => '$84.00', 'oldPrice' => null, 'discount' => null],
        ['image' => 'assets/images/product-hoodie.png', 'badge' => 'Sale', 'badgeBg' => 'var(--color-primary)', 'name' => 'Fleece Hoodie', 'weight' => '220g', 'desc' => 'Premium cotton blend', 'rating' => '4.3 (+710)', 'price' => '$99.00', 'oldPrice' => '$120.00', 'discount' => '18% Off'],
        ['image' => 'assets/images/product-sneaker.png', 'badge' => 'New', 'badgeBg' => 'var(--color-accent-purple)', 'name' => 'Trail Sneakers', 'weight' => '270g', 'desc' => 'Premium cotton blend', 'rating' => '4.4 (+560)', 'price' => '$115.00', 'oldPrice' => null, 'discount' => null],
      ];
    @endphp
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] mt-12 flex flex-col gap-[16px] lg:gap-[34px]"
      style="
        background: linear-gradient(
          180deg,
          var(--color-yellow-bright) 0%,
          transparent 100%
        );
      "
    >
      <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
          New In
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
          <div class="swiper-wrapper" id="newInWrapper">
            @foreach ($newInProducts as $product)
              <div class="swiper-slide h-auto !w-[210px] lg:!w-[260px]">
                @include('themes.elora.pages.home-v2.sections.partials.product_card', ['p' => $product])
              </div>
            @endforeach
          </div>
        </div>
        <button
          id="newInPrev"
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
          id="newInNext"
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
