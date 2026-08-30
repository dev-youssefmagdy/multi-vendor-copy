@php
  $newInProducts6 = [
    ['image' => 'images/product-placeholder.svg', 'name' => 'Street Sneakers', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'],
    ['image' => 'images/product-placeholder.svg', 'name' => 'Essential Hoodie', 'weight' => '200g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)'],
    ['image' => 'images/product-placeholder.svg', 'name' => 'Runner Sneakers', 'weight' => '260g', 'price' => '$110.00', 'oldPrice' => '$130.00', 'discount' => '15% Off', 'rating' => '4.6 (+1.2k)'],
    ['image' => 'images/product-placeholder.svg', 'name' => 'Oversized Hoodie', 'weight' => '210g', 'price' => '$95.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.5 (+620)'],
    ['image' => 'images/product-placeholder.svg', 'name' => 'Pullover Hoodie', 'weight' => '220g', 'price' => '$91.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.6 (+890)'],
    ['image' => 'images/product-placeholder.svg', 'name' => 'Court Sneakers', 'weight' => '255g', 'price' => '$92.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.3 (+390)'],
    ['image' => 'images/product-placeholder.svg', 'name' => 'Zip Hoodie', 'weight' => '225g', 'price' => '$97.00', 'oldPrice' => '$115.00', 'discount' => '15% Off', 'rating' => '4.4 (+640)'],
    ['image' => 'images/product-placeholder.svg', 'name' => 'Trail Sneakers', 'weight' => '270g', 'price' => '$115.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.4 (+560)'],
  ];
@endphp
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] flex flex-col gap-[16px] lg:gap-[34px]" style="background: var(--color-section-cream)">
  <div class="flex items-center justify-between">
    <h2 class="font-medium text-[22px] lg:text-[32px]" style="color: var(--color-text-primary)">New In</h2>
    <a href="#" class="text-[14px] lg:text-[24px] tracking-[0.5px] lg:tracking-[0.9px]" style="color: var(--color-text-primary)">see all</a>
  </div>
  <div class="relative">
    <div class="swiper card-swiper">
      <div class="swiper-wrapper" id="newInWrapper">
        @foreach ($newInProducts6 as $product)
          <div class="swiper-slide h-auto !w-[210px] lg:!w-[260px]">
            @include('themes.elora.pages.home-v6.sections.partials.product_card', ['p' => $product])
          </div>
        @endforeach
      </div>
    </div>
  </div>
</section>
