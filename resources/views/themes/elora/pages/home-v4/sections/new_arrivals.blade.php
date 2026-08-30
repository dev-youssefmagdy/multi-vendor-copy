@php
  $PLACEHOLDER_IMG = asset('elora-4/assets/images/product-placeholder.svg');
  $newInProducts4 = [
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Essential Hoodie', 'weight' => '200g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Graphic Hoodie', 'weight' => '210g', 'price' => '$95.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.5 (+620)'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Court Sneakers', 'weight' => '250g', 'price' => '$92.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.3 (+390)'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Wireless Headphones', 'weight' => '180g', 'price' => '$79.00', 'oldPrice' => '$99.00', 'discount' => '20% Off', 'rating' => '4.4 (+560)'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Classic Watch', 'weight' => '90g', 'price' => '$149.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.6 (+1.2k)'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Wireless Mouse', 'weight' => '120g', 'price' => '$39.00', 'oldPrice' => '$49.00', 'discount' => '20% Off', 'rating' => '4.1 (+430)'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Oversized Hoodie', 'weight' => '230g', 'price' => '$88.00', 'oldPrice' => '$105.00', 'discount' => '16% Off', 'rating' => '4.5 (+980)'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Retro Sneakers', 'weight' => '260g', 'price' => '$110.00', 'oldPrice' => '$130.00', 'discount' => '15% Off', 'rating' => '4.6 (+1.4k)'],
  ];
@endphp
<section
  class="pattern-newin mt-[64px] px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] flex flex-col gap-[16px] lg:gap-[34px]"
>
  <div class="flex items-center justify-between">
    <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
      New In
    </h2>
    <a href="#" class="text-[14px] lg:text-[20px] tracking-[0.5px]" style="color: var(--color-brand-orange-bright)">see all</a>
  </div>
  <div class="relative">
    <div class="swiper card-swiper newin-swiper">
      <div class="swiper-wrapper" id="newInWrapper">
        @foreach ($newInProducts4 as $product)
          <div class="swiper-slide h-auto !w-[210px] lg:!w-[260px]">
            @include('themes.elora.pages.home-v4.sections.partials.product_card', ['p' => $product])
          </div>
        @endforeach
      </div>
    </div>
  </div>
</section>
