{{-- No direct counterpart in elora-4/index.html; built to match its texture/orange visual language --}}
@php
  $PLACEHOLDER_IMG = asset('elora-4/assets/images/product-placeholder.svg');
  $tabbedTabs = ['New In', 'Best Sellers', 'Top Rated'];
  $tabbedProducts = [
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Essential Hoodie', 'weight' => '200g', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Court Sneakers', 'weight' => '250g', 'rating' => '4.3 (+390)', 'price' => '$92.00', 'oldPrice' => null, 'discount' => null],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Wireless Headphones', 'weight' => '180g', 'rating' => '4.4 (+560)', 'price' => '$79.00', 'oldPrice' => '$99.00', 'discount' => '20% Off'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Classic Watch', 'weight' => '90g', 'rating' => '4.6 (+1.2k)', 'price' => '$149.00', 'oldPrice' => null, 'discount' => null],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Wireless Mouse', 'weight' => '120g', 'rating' => '4.1 (+430)', 'price' => '$39.00', 'oldPrice' => '$49.00', 'discount' => '20% Off'],
  ];
@endphp
<section
  class="px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col gap-[16px] lg:gap-[24px]"
  style="background: var(--color-bg-main)"
>
  <h2 class="font-semibold text-[22px] lg:text-[32px] text-black text-center tracking-[0.5px]">
    Featured Products
  </h2>
  <div class="flex items-center justify-center gap-[10px] lg:gap-[16px] flex-wrap">
    @foreach ($tabbedTabs as $i => $tab)
      <button
        type="button"
        class="tabbed-pill flex items-center justify-center rounded-full px-[18px] lg:px-[24px] h-[36px] lg:h-[44px] text-[13px] lg:text-[15px] font-medium tracking-[0.3px] cursor-pointer {{ $i === 0 ? 'active' : '' }}"
        @if ($i === 0)
          style="background: var(--color-brand-orange-bright); color: #fff; border: 1px solid var(--color-brand-orange-bright);"
        @else
          style="background: transparent; color: var(--color-text-primary); border: 1px solid var(--color-stroke);"
        @endif
      >
        {{ $tab }}
      </button>
    @endforeach
  </div>
  <div class="grid grid-cols-2 lg:grid-cols-5 gap-[12px] lg:gap-[16px]">
    @foreach ($tabbedProducts as $product)
      <div class="h-[300px] lg:h-[360px]">
        @include('themes.elora.pages.home-v4.sections.partials.product_card', ['p' => $product])
      </div>
    @endforeach
  </div>
</section>
