@php
  $PLACEHOLDER_IMG = asset('elora-4/assets/images/product-placeholder.svg');
  $bestSellerProducts4 = [
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Essential Hoodie', 'weight' => '200g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'progress' => 83, 'ordered' => '5 ordered last 30 min'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Retro Sneakers', 'weight' => '200g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'progress' => 60, 'ordered' => '3 ordered last 30 min'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Studio Headphones', 'weight' => '200g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'progress' => 45, 'ordered' => '2 ordered last 30 min'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Wireless Mouse', 'weight' => '200g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'progress' => 70, 'ordered' => '4 ordered last 30 min'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Essential Hoodie', 'weight' => '200g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'progress' => 90, 'ordered' => '6 ordered last 30 min'],
    ['image' => $PLACEHOLDER_IMG, 'name' => 'Retro Sneakers', 'weight' => '200g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'progress' => 55, 'ordered' => '3 ordered last 30 min'],
  ];
@endphp
<section
  class="texture-bg px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col items-center gap-[16px] lg:gap-[24px]"
>
  <img
    src="{{ asset('elora-4/assets/images/best-seller-texture.png') }}"
    alt=""
    class="texture-overlay"
  />
  <h2 class="relative font-bold text-[24px] lg:text-[36px] text-white">
    Best Seller
  </h2>
  <div class="relative w-full">
    <div class="swiper card-swiper">
      <div class="swiper-wrapper" id="bestSellerWrapper">
        @foreach ($bestSellerProducts4 as $product)
          @include('themes.elora.pages.home-v4.sections.partials.best_seller_card', ['p' => $product])
        @endforeach
      </div>
    </div>
    <button
      id="bestSellerPrev"
      type="button"
      aria-label="Previous"
      class="swiper-nav-btn swiper-nav-prev"
    >
      <img src="{{ asset('elora-4/assets/icons/arrow-down.svg') }}" class="size-[14px] rotate-90" alt="" />
    </button>
    <button
      id="bestSellerNext"
      type="button"
      aria-label="Next"
      class="swiper-nav-btn swiper-nav-next"
    >
      <img src="{{ asset('elora-4/assets/icons/arrow-down.svg') }}" class="size-[14px] -rotate-90" alt="" />
    </button>
  </div>
  <button
    type="button"
    class="relative border border-white rounded-full h-[48px] lg:h-[64px] px-[32px] flex items-center justify-center cursor-pointer"
  >
    <span class="font-medium text-white text-[16px] lg:text-[20px] tracking-[0.5px]">Explore all</span>
  </button>
</section>
