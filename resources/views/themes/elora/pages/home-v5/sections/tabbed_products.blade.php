    {{-- No direct counterpart in the elora-5 mockup; built consistent with its visual
         language (category-pill tabs above a product grid) using the shared product card. --}}
    @php
      $tabProducts = [
        'All' => [
          ['name' => 'Essential Hoodie', 'weight' => '200g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'stock' => 'Only 5 left'],
          ['name' => 'Essential Shoes', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.6 (+1.4k)', 'stock' => 'Only 5 left'],
          ['name' => 'Wireless Mouse', 'weight' => '120g', 'price' => '$39.00', 'oldPrice' => '$49.00', 'discount' => '20% Off', 'rating' => '4.1 (+430)', 'stock' => 'Only 8 left'],
          ['name' => 'Graphic Tee', 'weight' => '150g', 'price' => '$45.00', 'oldPrice' => '$55.00', 'discount' => '18% Off', 'rating' => '4.3 (+390)', 'stock' => 'Only 6 left'],
        ],
        'Fashion' => [
          ['name' => 'Classic Pants', 'weight' => '300g', 'price' => '$79.00', 'oldPrice' => '$99.00', 'discount' => '20% Off', 'rating' => '4.4 (+960)', 'stock' => 'Only 4 left'],
          ['name' => 'Fleece Hoodie', 'weight' => '220g', 'price' => '$99.00', 'oldPrice' => '$120.00', 'discount' => '18% Off', 'rating' => '4.3 (+710)', 'stock' => 'Only 5 left'],
          ['name' => 'Runner Sneakers', 'weight' => '260g', 'price' => '$110.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.6 (+1.2k)', 'alt' => true, 'stock' => 'Only 3 left'],
          ['name' => 'Tailored Pants', 'weight' => '310g', 'price' => '$84.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.1 (+430)', 'stock' => 'Only 6 left'],
        ],
        'Electronics' => [
          ['name' => 'Wireless Headphones', 'weight' => '180g', 'price' => '$79.00', 'oldPrice' => '$99.00', 'discount' => '20% Off', 'rating' => '4.4 (+560)', 'stock' => 'Only 3 left'],
          ['name' => 'Wireless Earbuds', 'weight' => '60g', 'price' => '$59.00', 'oldPrice' => '$79.00', 'discount' => '25% Off', 'rating' => '4.5 (+920)', 'stock' => 'Only 7 left'],
          ['name' => 'Classic Watch', 'weight' => '90g', 'price' => '$149.00', 'oldPrice' => '$179.00', 'discount' => '17% Off', 'rating' => '4.6 (+1.2k)', 'stock' => 'Only 9 left'],
          ['name' => 'Ergo Mouse', 'weight' => '115g', 'price' => '$35.00', 'oldPrice' => '$45.00', 'discount' => '22% Off', 'rating' => '4.0 (+210)', 'stock' => 'Only 9 left'],
        ],
      ];
    @endphp
    <section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[40px] flex flex-col gap-[16px] lg:gap-[28px]" style="background: var(--color-page-bg)">
      <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px]" style="color: var(--color-black)">Shop the Collection</h2>
        <a href="#" class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]" style="color: var(--color-primary)">see all</a>
      </div>
      <div class="flex items-center gap-[10px] lg:gap-[14px] overflow-x-auto no-scrollbar" data-v5-tabs>
        @foreach ($tabProducts as $tabName => $products)
          <button
            type="button"
            class="v5-tab-pill shrink-0 rounded-full px-[18px] lg:px-[24px] h-[38px] lg:h-[46px] text-[13px] lg:text-[16px] font-medium tracking-[0.3px] whitespace-nowrap cursor-pointer transition-colors {{ $loop->first ? 'is-active' : '' }}"
            data-v5-tab-target="v5-tab-panel-{{ $loop->index }}"
          >
            {{ $tabName }}
          </button>
        @endforeach
      </div>
      @foreach ($tabProducts as $tabName => $products)
        <div id="v5-tab-panel-{{ $loop->index }}" class="v5-tab-panel grid grid-cols-2 lg:grid-cols-4 gap-[12px] lg:gap-[16px]" @if (!$loop->first) hidden @endif>
          @foreach ($products as $product)
            <div class="h-[360px] lg:h-[420px]">
              @include('themes.elora.pages.home-v5.sections.partials.product_card', ['p' => $product])
            </div>
          @endforeach
        </div>
      @endforeach
    </section>
