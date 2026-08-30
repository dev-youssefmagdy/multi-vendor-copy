    @php
      $newInImg = asset('elora-4/assets/images/product-placeholder.svg');
      $newInProducts = [
        ['image' => $newInImg, 'name' => 'Essential Hoodie', 'weight' => '200g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'],
        ['image' => $newInImg, 'name' => 'Graphic Hoodie', 'weight' => '210g', 'price' => '$95.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.5 (+620)'],
        ['image' => $newInImg, 'name' => 'Court Sneakers', 'weight' => '250g', 'price' => '$92.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.3 (+390)'],
        ['image' => $newInImg, 'name' => 'Wireless Headphones', 'weight' => '180g', 'price' => '$79.00', 'oldPrice' => '$99.00', 'discount' => '20% Off', 'rating' => '4.4 (+560)'],
        ['image' => $newInImg, 'name' => 'Classic Watch', 'weight' => '90g', 'price' => '$149.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.6 (+1.2k)'],
        ['image' => $newInImg, 'name' => 'Wireless Mouse', 'weight' => '120g', 'price' => '$39.00', 'oldPrice' => '$49.00', 'discount' => '20% Off', 'rating' => '4.1 (+430)'],
        ['image' => $newInImg, 'name' => 'Oversized Hoodie', 'weight' => '230g', 'price' => '$88.00', 'oldPrice' => '$105.00', 'discount' => '16% Off', 'rating' => '4.5 (+980)'],
        ['image' => $newInImg, 'name' => 'Retro Sneakers', 'weight' => '260g', 'price' => '$110.00', 'oldPrice' => '$130.00', 'discount' => '15% Off', 'rating' => '4.6 (+1.4k)'],
      ];
      $newInGroups = array_chunk($newInProducts, 3);
    @endphp
    <!-- ============ NEW IN ============ -->
    <section
      class="pattern-newin mt-[64px] px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] flex flex-col gap-[16px] lg:gap-[34px]"
    >
      <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
          New In
        </h2>
        <a
          href="#"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-brand-orange-bright)"
          >see all</a
        >
      </div>
      <div class="relative">
        <div class="swiper card-swiper newin-swiper">
          <div class="swiper-wrapper" id="newInWrapper">
            @foreach ($newInGroups as $group)
              @php [$left, $top, $bottom] = array_pad($group, 3, null); @endphp
              @if ($left)
                <div class="swiper-slide h-auto">
                  <div class="flex gap-[16px] h-full">
                    <div class="shrink-0 w-[240px]">
                      @include('themes.elora.pages.home-v4.sections.partials.product_card', ['p' => $left])
                    </div>
                    @if ($top || $bottom)
                      <div class="flex-1 min-w-0 flex flex-col gap-[21px]">
                        @if ($top)
                          <div class="flex-1 min-h-0">
                            @include('themes.elora.pages.home-v4.sections.partials.new_in_side_card', ['p' => $top])
                          </div>
                        @endif
                        @if ($bottom)
                          <div class="flex-1 min-h-0">
                            @include('themes.elora.pages.home-v4.sections.partials.new_in_side_card', ['p' => $bottom])
                          </div>
                        @endif
                      </div>
                    @endif
                  </div>
                </div>
              @endif
            @endforeach
          </div>
        </div>
      </div>
    </section>
