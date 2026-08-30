@php
  $__ph = asset('elora-3/assets/images/product-placeholder.svg');
  $__progressData = [
    ['progress' => 83, 'label' => '5 ordered last 30 min', 'urgency' => 'var(--color-success)'],
    ['progress' => 65, 'label' => '4 ordered last 30 min', 'urgency' => 'var(--color-error)'],
    ['progress' => 45, 'label' => '3 ordered last 30 min', 'urgency' => 'var(--color-success)'],
    ['progress' => 72, 'label' => '5 ordered last 30 min', 'urgency' => 'var(--color-error)'],
    ['progress' => 58, 'label' => '3 ordered last 30 min', 'urgency' => 'var(--color-success)'],
    ['progress' => 90, 'label' => '6 ordered last 30 min', 'urgency' => 'var(--color-error)'],
  ];
  $__trending = array_map(fn ($d) => [
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
<section class="px-[16px] lg:px-[56px] py-[48px] flex flex-col gap-[16px] lg:gap-[24px] bg-[#F0F0F0]">
  <div class="flex items-center justify-between">
    <h2 class="font-medium text-[22px] lg:text-[32px]" style="color: var(--color-text-primary)">Trending Now</h2>
    <a href="#" class="text-[14px] lg:text-[20px] tracking-[0.5px]" style="color: var(--color-success)">see all</a>
  </div>
  <div class="relative">
    <div class="swiper card-swiper">
      <div class="swiper-wrapper">
        @foreach ($__trending as $p)
          <div class="swiper-slide h-auto !w-[210px] lg:!w-[260px]">
            @include('themes.elora.pages.home-v3.sections.partials.product_card', ['p' => $p])
          </div>
        @endforeach
      </div>
    </div>
    <button id="trendingPrev" type="button" aria-label="Previous" class="swiper-nav-btn swiper-nav-prev">
      <img src="{{ asset('elora-3/assets/icons/arrow-down.svg') }}" class="size-[14px] rotate-90" alt="" />
    </button>
    <button id="trendingNext" type="button" aria-label="Next" class="swiper-nav-btn swiper-nav-next">
      <img src="{{ asset('elora-3/assets/icons/arrow-down.svg') }}" class="size-[14px] -rotate-90" alt="" />
    </button>
  </div>
</section>
