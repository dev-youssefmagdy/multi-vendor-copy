{{-- invented section, no direct elora-2 counterpart --}}
{{-- Built as a tabbed product grid (New / Best Sellers / Trending) reusing elora-2's product
     data shapes and the shared product_card partial, styled with elora-2's green/cream/yellow
     palette so it reads as a natural extension of the mockup rather than a foreign block. --}}
@php
  $tabbedGroups6 = [
    'new' => [
      ['image' => 'images/product-placeholder.svg', 'name' => 'Pullover Hoodie', 'weight' => '220g', 'price' => '$91.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.6 (+890)'],
      ['image' => 'images/product-placeholder.svg', 'name' => 'Court Sneakers', 'weight' => '255g', 'price' => '$92.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.3 (+390)'],
      ['image' => 'images/product-placeholder.svg', 'name' => 'Zip Hoodie', 'weight' => '225g', 'price' => '$97.00', 'oldPrice' => '$115.00', 'discount' => '15% Off', 'rating' => '4.4 (+640)'],
      ['image' => 'images/product-placeholder.svg', 'name' => 'Trail Sneakers', 'weight' => '270g', 'price' => '$115.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.4 (+560)'],
    ],
    'best' => [
      ['image' => 'images/product-placeholder.svg', 'name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'badge' => '30% OFF', 'badgeBg' => 'var(--color-primary)', 'badgeColor' => 'var(--color-white)', 'weightColor' => 'var(--color-primary)'],
      ['image' => 'images/product-placeholder.svg', 'name' => 'Weekend Backpack', 'weight' => '480g', 'price' => '$99.00', 'oldPrice' => '$120.00', 'discount' => '18% Off', 'rating' => '4.3 (+710)', 'weightColor' => 'var(--color-primary)'],
      ['image' => 'images/product-placeholder.svg', 'name' => 'Smart Watch', 'weight' => '60g', 'price' => '$129.00', 'oldPrice' => '$159.00', 'discount' => '19% Off', 'rating' => '4.6 (+1.1k)', 'weightColor' => 'var(--color-primary)'],
      ['image' => 'images/product-placeholder.svg', 'name' => 'Essential Shoes', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'weightColor' => 'var(--color-primary)'],
    ],
    'trending' => [
      ['image' => 'images/product-placeholder.svg', 'name' => 'Classic Tee', 'weight' => '180g', 'price' => '$45.00', 'oldPrice' => '$60.00', 'discount' => '25% Off', 'rating' => '4.1 (+430)'],
      ['image' => 'images/product-placeholder.svg', 'name' => 'Fitness Watch', 'weight' => '55g', 'price' => '$109.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.4 (+560)', 'weightColor' => 'var(--color-primary)'],
      ['image' => 'images/product-placeholder.svg', 'name' => 'Fleece Hoodie', 'weight' => '220g', 'price' => '$99.00', 'oldPrice' => '$120.00', 'discount' => '18% Off', 'rating' => '4.3 (+710)'],
      ['image' => 'images/product-placeholder.svg', 'name' => 'Classic Sneakers', 'weight' => '250g', 'price' => '$99.00', 'oldPrice' => '$119.00', 'discount' => '17% Off', 'rating' => '4.3 (+700)'],
    ],
  ];
  $tabbedLabels6 = ['new' => 'New', 'best' => 'Best Sellers', 'trending' => 'Trending'];
@endphp
<section class="tabbed-products-v6 px-[16px] lg:px-[56px] py-[24px] lg:py-[40px] flex flex-col gap-[16px] lg:gap-[24px]" style="background: var(--color-bg-main)">
  <div class="flex items-center justify-between flex-wrap gap-[12px]">
    <h2 class="font-medium text-[22px] lg:text-[32px]" style="color: var(--color-text-primary)">Discover Products</h2>
    <div class="tabbed-tabs flex items-center gap-[8px] lg:gap-[12px]" role="tablist">
      @foreach ($tabbedLabels6 as $key => $label)
        <button
          type="button"
          class="tabbed-tab-btn {{ $loop->first ? 'is-active' : '' }}"
          data-tab-target="tabbed-panel-{{ $key }}"
          role="tab"
          aria-selected="{{ $loop->first ? 'true' : 'false' }}"
        >{{ $label }}</button>
      @endforeach
    </div>
  </div>

  @foreach ($tabbedGroups6 as $key => $products)
    <div id="tabbed-panel-{{ $key }}" class="tabbed-panel-v6 {{ $loop->first ? '' : 'hidden' }}" role="tabpanel">
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-[12px] lg:gap-[16px]">
        @foreach ($products as $product)
          @include('themes.elora.pages.home-v6.sections.partials.product_card', ['p' => $product])
        @endforeach
      </div>
    </div>
  @endforeach
</section>
