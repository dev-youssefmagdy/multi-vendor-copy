    @php
      $heroSlides = [
        ['title' => 'Explore', 'titleRest' => 'New Products', 'cta' => 'Shop Now'],
        ['title' => 'Flash', 'titleRest' => 'Sale Up To 50%', 'cta' => 'Shop Deals'],
        ['title' => 'Best', 'titleRest' => 'Sellers Are Here', 'cta' => 'Discover More'],
      ];
    @endphp
    <section class="relative">
      <div class="swiper hero-swiper">
        <div class="swiper-wrapper">
          @foreach ($heroSlides as $slide)
            <div class="swiper-slide">
              <div class="relative flex items-center h-[254px] lg:h-[537px] overflow-hidden px-[18px] lg:px-[56px] py-[22px]">
                <img
                  src="{{ asset('elora-5/assets/images/hero-desktop.png') }}"
                  alt=""
                  class="absolute inset-0 h-full w-full object-cover"
                />
                <div
                  class="absolute inset-0"
                  style="background: linear-gradient(180deg, rgba(19, 32, 146, 0) 0%, rgba(19, 32, 146, 0.57) 100%);"
                ></div>
                <div class="relative flex flex-col gap-[12px] lg:gap-[19px] items-start justify-center w-[150px] lg:w-[412px]">
                  <h1 class="font-bold text-[24px] lg:text-[64px] leading-[1.05] text-white tracking-[0.5px]">
                    <span style="color: var(--color-primary)">{{ $slide['title'] }}</span> {{ $slide['titleRest'] }}
                  </h1>
                  <button
                    type="button"
                    class="bg-white flex h-[38px] lg:h-[68px] items-center justify-center rounded-full px-[24px] lg:px-[44px] cursor-pointer w-full lg:w-auto"
                  >
                    <span class="font-medium text-[14px] lg:text-[24px] tracking-[0.5px]" style="color: var(--color-primary)">{{ $slide['cta'] }}</span>
                  </button>
                </div>
              </div>
            </div>
          @endforeach
        </div>
        <div class="hero-pagination"></div>
      </div>
    </section>
