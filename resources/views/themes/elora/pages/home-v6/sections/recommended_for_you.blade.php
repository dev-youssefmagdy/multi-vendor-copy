    @php
      $recommendedProducts = [
        ['image' => 'images/product-placeholder.svg', 'name' => 'Court Sneakers', 'weight' => '255g', 'price' => '$92.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.3 (+390)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Zip Hoodie', 'weight' => '225g', 'price' => '$97.00', 'oldPrice' => '$115.00', 'discount' => '15% Off', 'rating' => '4.4 (+640)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Weekend Backpack', 'weight' => '480g', 'price' => '$89.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.2 (+850)', 'weightColor' => 'var(--color-primary)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Essential Shoes', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'weightColor' => 'var(--color-primary)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Classic Tee', 'weight' => '180g', 'price' => '$45.00', 'oldPrice' => '$60.00', 'discount' => '25% Off', 'rating' => '4.1 (+430)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Smart Watch', 'weight' => '60g', 'price' => '$129.00', 'oldPrice' => '$159.00', 'discount' => '19% Off', 'rating' => '4.6 (+1.1k)', 'weightColor' => 'var(--color-primary)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Trail Sneakers', 'weight' => '270g', 'price' => '$115.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.4 (+560)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Pullover Hoodie', 'weight' => '220g', 'price' => '$91.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.6 (+890)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'weightColor' => 'var(--color-primary)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Street Sneakers', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Oversized Hoodie', 'weight' => '210g', 'price' => '$95.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.5 (+620)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Trail Backpack', 'weight' => '460g', 'price' => '$99.00', 'oldPrice' => '$120.00', 'discount' => '18% Off', 'rating' => '4.3 (+710)', 'weightColor' => 'var(--color-primary)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Runner Sneakers', 'weight' => '260g', 'price' => '$110.00', 'oldPrice' => '$130.00', 'discount' => '15% Off', 'rating' => '4.6 (+1.2k)', 'weightColor' => 'var(--color-primary)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Relaxed Tee', 'weight' => '190g', 'price' => '$42.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.0 (+210)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Fitness Watch', 'weight' => '55g', 'price' => '$109.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.4 (+560)', 'weightColor' => 'var(--color-primary)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Fleece Hoodie', 'weight' => '220g', 'price' => '$99.00', 'oldPrice' => '$120.00', 'discount' => '18% Off', 'rating' => '4.3 (+710)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Classic Sneakers', 'weight' => '250g', 'price' => '$99.00', 'oldPrice' => '$119.00', 'discount' => '17% Off', 'rating' => '4.3 (+700)'],
        ['image' => 'images/product-placeholder.svg', 'name' => 'Tailored Pants', 'weight' => '310g', 'price' => '$84.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.1 (+430)', 'weightColor' => 'var(--color-primary)'],
      ];
    @endphp
    <!-- ============ RECOMMENDED FOR YOU ============ -->
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] flex flex-col gap-[16px] lg:gap-[34px]"
    >
      <div class="flex items-center justify-between">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-text-primary)"
        >
          Recommended For You
        </h2>
        <a
          href="#"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-accent-green)"
          >see all</a
        >
      </div>
      <div
        class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-[12px] lg:gap-[16px]"
      >
        @foreach ($recommendedProducts as $p)
          @include('themes.elora.pages.home-v6.sections.partials.grid_card', ['p' => $p])
        @endforeach
      </div>
    </section>
