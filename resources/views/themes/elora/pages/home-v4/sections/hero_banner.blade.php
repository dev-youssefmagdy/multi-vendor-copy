@php
  $heroSlides = [
    ['tag' => 'NEW USER', 'title' => 'Explore New<br>Products', 'subtitle' => 'New user exclusive deal', 'cta' => 'Shop Now'],
    ['tag' => '20% OFF', 'title' => 'Flash Sale<br>Up To 50%', 'subtitle' => 'For a limited time only', 'cta' => 'Shop Deals'],
    ['tag' => 'BEST SELLER', 'title' => 'New Season<br>Arrivals', 'subtitle' => "Discover what's trending", 'cta' => 'Discover More'],
  ];
@endphp
<section class="relative">
  <div class="swiper hero-swiper">
    <div class="swiper-wrapper">
      @foreach ($heroSlides as $slide)
        <div class="swiper-slide">
          <div
            class="relative flex items-end lg:items-center h-[321px] lg:h-[527px] overflow-hidden"
            style="background: var(--color-brand-orange-bright)"
          >
            <div
              class="flex flex-col gap-[18px] lg:gap-[24px] items-start justify-center px-[20px] lg:px-[74px] py-[24px] lg:py-0 w-[70%] lg:w-[548px] shrink-0"
            >
              <div class="bg-white rounded-[8px] px-[14px] lg:px-[18px] py-[3px] lg:py-[4px]">
                <p class="font-black text-[16px] lg:text-[26px] tracking-[1.3px] lg:tracking-[2.2px]" style="color: var(--color-brand-orange)">
                  {{ $slide['tag'] }}
                </p>
              </div>
              <h1 class="font-black text-[28px] lg:text-[57px] leading-[1.05] text-white">
                {!! $slide['title'] !!}
              </h1>
              <p class="font-normal text-[14px] lg:text-[24px]" style="color: var(--color-hero-subtitle)">
                {{ $slide['subtitle'] }}
              </p>
              <button type="button" class="bg-white flex h-[38px] lg:h-[70px] items-center justify-center rounded-full px-[24px] lg:px-[44px] cursor-pointer">
                <span class="font-bold text-[14px] lg:text-[26px]" style="color: var(--color-brand-orange)">{{ $slide['cta'] }}</span>
              </button>
            </div>
            <div class="absolute right-0 top-0 h-full w-[45%] lg:w-[892px] lg:static lg:flex-1">
              <img
                src="{{ asset('elora-4/assets/images/hero-woman.png') }}"
                alt="{{ $slide['subtitle'] }}"
                class="h-full w-full object-cover"
              />
            </div>
          </div>
        </div>
      @endforeach
    </div>
    <div class="hero-pagination"></div>
  </div>
</section>
