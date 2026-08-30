@php
  $__heroSlides = [
    ['badge' => 'NEW USER', 'title' => 'Explore New Products', 'subtitle' => 'New user exclusive deal', 'cta' => 'Shop Now'],
    ['badge' => 'FLASH SALE', 'title' => 'Up To 50% Off', 'subtitle' => 'Limited time only', 'cta' => 'Shop Deals'],
    ['badge' => 'NEW SEASON', 'title' => 'Fresh Styles Just Landed', 'subtitle' => 'Discover the latest arrivals', 'cta' => 'Discover More'],
  ];
@endphp
<section class="relative">
  <div class="swiper hero-swiper">
    <div class="swiper-wrapper">
      @foreach ($__heroSlides as $__slide)
        <div class="swiper-slide">
          <div class="relative flex flex-col items-center lg:items-start justify-end h-[200px] lg:h-[524px] px-[20px] lg:px-[56px] py-[18px] lg:py-[32px] overflow-hidden">
            <img
              src="{{ asset('elora-3/assets/images/hero-mobile.jpg') }}"
              alt=""
              class="lg:hidden absolute inset-0 h-full w-full object-cover"
              style="background: var(--color-hero-placeholder)"
            />
            <img
              src="{{ asset('elora-3/assets/images/hero-desktop.jpg') }}"
              alt=""
              class="hidden lg:block absolute inset-0 h-full w-full object-cover"
              style="background: var(--color-hero-placeholder)"
            />
            <div
              class="absolute inset-0"
              style="background: linear-gradient(90deg, rgba(0,0,0,0.65) 0%, rgba(0,0,0,0.1) 60%, rgba(0,0,0,0) 100%);"
            ></div>
            <div class="relative flex flex-col gap-[8px] lg:gap-[21px] items-start justify-center w-[210px] lg:w-[530px]">
              <span
                class="text-white font-black text-[12px] lg:text-[31px] tracking-[1px] lg:tracking-[2.6px] rounded-[4px] lg:rounded-[10px] px-[8px] lg:px-[21px] py-[2px] lg:py-[5px]"
                style="background: var(--color-brand-pink)"
              >{{ $__slide['badge'] }}</span>
              <h1 class="font-black text-[26px] lg:text-[68px] text-white leading-[1.1]">{{ $__slide['title'] }}</h1>
              <p class="text-[11px] lg:text-[29px] leading-tight" style="color: var(--color-hero-subtitle)">{{ $__slide['subtitle'] }}</p>
              <button
                type="button"
                class="flex h-[32px] lg:h-[84px] items-center justify-center rounded-full px-[20px] lg:px-[52px] cursor-pointer mt-[8px] lg:mt-[10px]"
                style="background: var(--color-brand-pink)"
              >
                <span class="font-bold text-[12px] lg:text-[31px] text-white">{{ $__slide['cta'] }}</span>
              </button>
            </div>
          </div>
        </div>
      @endforeach
    </div>
    <div class="hero-pagination"></div>
  </div>
</section>
