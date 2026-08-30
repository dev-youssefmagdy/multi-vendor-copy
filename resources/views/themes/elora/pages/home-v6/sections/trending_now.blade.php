@php
  $trendingProducts6 = [
    ['name' => 'Essential Shoes', 'weight' => '250g', 'progress' => 83, 'ordered' => '5 ordered last 30 min', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off'],
    ['name' => 'Essential Shoes', 'weight' => '250g', 'progress' => 65, 'ordered' => '3 ordered last 30 min', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => null, 'discount' => null],
    ['name' => 'Essential Shoes', 'weight' => '250g', 'progress' => 45, 'ordered' => '2 ordered last 30 min', 'rating' => '4.1 (+430)', 'price' => '$79.00', 'oldPrice' => '$99.00', 'discount' => '20% Off'],
    ['name' => 'Runner Sneakers', 'weight' => '260g', 'progress' => 72, 'ordered' => '4 ordered last 30 min', 'rating' => '4.6 (+1.2k)', 'price' => '$110.00', 'oldPrice' => '$130.00', 'discount' => '15% Off'],
  ];
@endphp
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[28px] flex flex-col gap-[16px] lg:gap-[24px]">
  <div class="flex items-center justify-between">
    <h2 class="font-medium text-[22px] lg:text-[32px]" style="color: var(--color-text-primary)">Trending Now</h2>
    <a href="#" class="text-[14px] lg:text-[20px] tracking-[0.5px]" style="color: var(--color-accent-green)">see all</a>
  </div>
  <div class="relative">
    <div class="swiper card-swiper trending-swiper">
      <div class="swiper-wrapper" id="trendingWrapper">
        @foreach ($trendingProducts6 as $product)
          <div class="swiper-slide h-auto !w-[280px] lg:!w-[420px]">
            @include('themes.elora.pages.home-v6.sections.partials.trending_card', ['p' => $product])
          </div>
        @endforeach
      </div>
    </div>
  </div>
</section>
