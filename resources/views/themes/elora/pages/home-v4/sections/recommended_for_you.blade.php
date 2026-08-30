@php
  $PLACEHOLDER_IMG = asset('elora-4/assets/images/product-placeholder.svg');
  $recommendedProducts4 = [
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Zip Hoodie', 'weight' => '215g', 'price' => '$97.00', 'oldPrice' => '$115.00', 'discount' => '15% Off', 'rating' => '4.4 (+640)'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Street Sneakers', 'weight' => '265g', 'price' => '$105.00', 'oldPrice' => '$125.00', 'discount' => '16% Off', 'rating' => '4.2 (+505)'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Over-ear Headphones', 'weight' => '185g', 'price' => '$85.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.3 (+700)'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Chrono Watch', 'weight' => '95g', 'price' => '$159.00', 'oldPrice' => '$189.00', 'discount' => '16% Off', 'rating' => '4.7 (+890)'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Ergo Mouse', 'weight' => '115g', 'price' => '$35.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.0 (+210)'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Pullover Hoodie', 'weight' => '225g', 'price' => '$91.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.6 (+890)'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Trail Sneakers', 'weight' => '270g', 'price' => '$115.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.4 (+560)'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Fleece Hoodie', 'weight' => '220g', 'price' => '$99.00', 'oldPrice' => '$120.00', 'discount' => '18% Off', 'rating' => '4.3 (+710)'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Studio Headphones', 'weight' => '190g', 'price' => '$99.00', 'oldPrice' => '$119.00', 'discount' => '17% Off', 'rating' => '4.3 (+700)'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Runner Sneakers', 'weight' => '260g', 'price' => '$110.00', 'oldPrice' => null, 'discount' => null, 'rating' => '4.6 (+1.2k)'],
  ];
@endphp
<section
  class="px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] flex flex-col gap-[16px] lg:gap-[34px]"
>
  <div class="flex items-center justify-between">
    <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
      Recommended For You
    </h2>
    <a href="#" class="text-[14px] lg:text-[20px] tracking-[0.5px]" style="color: var(--color-brand-orange-bright)">see all</a>
  </div>
  <div
    id="recommendedWrapper"
    class="grid grid-cols-2 lg:grid-cols-5 gap-[12px] lg:gap-[16px]"
  >
    @foreach ($recommendedProducts4 as $product)
      <div class="h-[300px] lg:h-[360px]">
        @include('themes.elora.pages.home-v4.sections.partials.product_card', ['p' => $product])
      </div>
    @endforeach
  </div>
</section>
