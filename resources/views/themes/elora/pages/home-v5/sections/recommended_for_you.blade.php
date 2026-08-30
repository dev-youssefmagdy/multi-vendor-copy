    @php
      $recommendedProducts = [
        ['name' => 'Zip Hoodie', 'weight' => '215g', 'price' => '$97.00', 'oldPrice' => '$115.00', 'discount' => '15% Off', 'rating' => '4.4 (+640)', 'stock' => 'Only 5 left'],
        ['name' => 'Street Sneakers', 'weight' => '265g', 'price' => '$105.00', 'oldPrice' => '$125.00', 'discount' => '16% Off', 'rating' => '4.2 (+505)', 'stock' => 'Only 4 left'],
        ['name' => 'Over-ear Headphones', 'weight' => '185g', 'price' => '$85.00', 'oldPrice' => '$99.00', 'discount' => '14% Off', 'rating' => '4.3 (+700)', 'stock' => 'Only 6 left'],
        ['name' => 'Chrono Watch', 'weight' => '95g', 'price' => '$159.00', 'oldPrice' => '$189.00', 'discount' => '16% Off', 'rating' => '4.7 (+890)', 'stock' => 'Only 3 left'],
        ['name' => 'Ergo Mouse', 'weight' => '115g', 'price' => '$35.00', 'oldPrice' => '$45.00', 'discount' => '22% Off', 'rating' => '4.0 (+210)', 'stock' => 'Only 9 left'],
        ['name' => 'Pullover Hoodie', 'weight' => '225g', 'price' => '$91.00', 'oldPrice' => '$110.00', 'discount' => '17% Off', 'rating' => '4.6 (+890)', 'stock' => 'Only 5 left'],
        ['name' => 'Trail Sneakers', 'weight' => '270g', 'price' => '$115.00', 'oldPrice' => '$135.00', 'discount' => '15% Off', 'rating' => '4.4 (+560)', 'stock' => 'Only 5 left'],
        ['name' => 'Fleece Hoodie', 'weight' => '220g', 'price' => '$99.00', 'oldPrice' => '$120.00', 'discount' => '18% Off', 'rating' => '4.3 (+710)', 'stock' => 'Only 5 left'],
      ];
    @endphp
    <!-- ============ RECOMMENDED FOR YOU ============ -->
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[40px] flex flex-col gap-[16px] lg:gap-[28px]"
    >
      <div class="flex items-center justify-between">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-black)"
        >
          Recommended For You
        </h2>
        <a
          href="#"
          class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-primary)"
          >see all</a
        >
      </div>
      <div
        class="grid grid-cols-2 lg:grid-cols-5 gap-[12px] lg:gap-[16px]"
      >
        @foreach ($recommendedProducts as $p)
          <div class="h-full">
            @include('themes.elora.pages.home-v5.sections.partials.product_card', ['p' => $p])
          </div>
        @endforeach
      </div>
    </section>
