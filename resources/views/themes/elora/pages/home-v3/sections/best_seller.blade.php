@php
  $__ph = asset('elora-3/assets/images/product-placeholder.svg');
  $__progressData = [
    ['progress' => 83, 'label' => '5 ordered last 30 min', 'urgency' => 'var(--color-success)'],
    ['progress' => 65, 'label' => '4 ordered last 30 min', 'urgency' => 'var(--color-error)'],
    ['progress' => 50, 'label' => '3 ordered last 30 min', 'urgency' => 'var(--color-success)'],
    ['progress' => 77, 'label' => '5 ordered last 30 min', 'urgency' => 'var(--color-error)'],
  ];
  $__bestSellers = array_map(fn ($d) => [
    'image' => $__ph,
    'badge' => '70% Sold',
    'badgeBg' => 'var(--color-accent-yellow)',
    'badgeText' => 'var(--color-text-primary)',
    'name' => 'Essential Hoodie',
    'weight' => '200g',
    'desc' => 'Premium cotton blend',
    'rating' => '4.2 (+850)',
    'price' => '$89.00',
    'oldPrice' => '$89.00',
    'discount' => '20% Off',
    'progress' => $d['progress'],
    'progressLabel' => $d['label'],
    'urgency' => $d['urgency'],
  ], $__progressData);
@endphp
<section class="pattern-bestseller px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col items-center gap-[16px] lg:gap-[24px]">
  <h2 class="font-medium text-[22px] lg:text-[32px] text-white">Best Seller</h2>
  <div class="relative w-full">
    <div class="swiper card-swiper bestseller-swiper">
      <div class="swiper-wrapper">
        @foreach ($__bestSellers as $p)
          <div class="swiper-slide h-auto !w-[210px] lg:!w-[260px]">
            @include('themes.elora.pages.home-v3.sections.partials.product_card', ['p' => $p, 'wide' => true])
          </div>
        @endforeach
      </div>
    </div>
    <button id="bestSellerPrev" type="button" aria-label="Previous" class="swiper-nav-btn swiper-nav-prev swiper-nav-btn-light">
      <img src="{{ asset('elora-3/assets/icons/arrow-down.svg') }}" class="size-[14px] rotate-90" alt="" />
    </button>
    <button id="bestSellerNext" type="button" aria-label="Next" class="swiper-nav-btn swiper-nav-next swiper-nav-btn-light">
      <img src="{{ asset('elora-3/assets/icons/arrow-down.svg') }}" class="size-[14px] -rotate-90" alt="" />
    </button>
  </div>
  <button type="button" class="border border-white rounded-full h-[44px] lg:h-[64px] px-[24px] lg:px-[32px] flex items-center justify-center cursor-pointer">
    <span class="font-medium text-white text-[14px] lg:text-[20px] tracking-[0.5px]">Explore all</span>
  </button>
</section>
