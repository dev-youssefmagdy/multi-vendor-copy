@php
    $__heroBanners = collect($banners ?? [])->take(3)->values();
@endphp
<!-- ============ HERO ============ -->
<section class="relative">
  <div class="swiper hero-swiper">
    <div class="swiper-wrapper">
      @forelse ($__heroBanners as $banner)
        <div class="swiper-slide">
          <div class="relative flex flex-col items-center lg:items-start justify-end h-[280px] lg:h-[543px] px-[18px] lg:px-[61px] py-[22px] lg:py-[49px] overflow-hidden">
            <img src="{{ $banner->image_path ?? asset('souqify-2/assets/images/hero-mobile.jpg') }}" alt="{{ $banner->title ?? $storeName }}" class="lg:hidden absolute inset-0 h-full w-full object-cover" />
            <img src="{{ $banner->image_path ?? asset('souqify-2/assets/images/hero-desktop.jpg') }}" alt="{{ $banner->title ?? $storeName }}" class="hidden lg:block absolute inset-0 h-full w-full object-cover" />
            <div class="absolute inset-0" style="background: rgba(0, 0, 0, 0.44)"></div>
            <div class="relative flex flex-col gap-[19px] lg:gap-[47px] items-center justify-center text-center lg:text-left w-full lg:w-[459px]">
              <h1 class="font-semibold text-[24px] lg:text-[60px] text-white tracking-[0.7px] leading-[1.05]">
                {!! $banner->title ? nl2br(e($banner->title)) : '<span style="color: var(--color-souqify-teal)">' . __('Explore') . '</span> ' . __('New Products') !!}
              </h1>
              <a href="{{ $banner->link ?? route('tenant.storefront.best-selling') }}" class="flex h-[38px] lg:h-[95px] items-center justify-center rounded-[34px] lg:rounded-full w-full lg:w-[459px] px-[8px] cursor-pointer" style="background: var(--color-souqify-teal)">
                <span class="font-medium text-[14px] lg:text-[35px] text-white tracking-[0.5px]">{{ __('Shop Now') }}</span>
              </a>
            </div>
          </div>
        </div>
      @empty
        <div class="swiper-slide">
          <div class="relative flex flex-col items-center lg:items-start justify-end h-[280px] lg:h-[543px] px-[18px] lg:px-[61px] py-[22px] lg:py-[49px] overflow-hidden">
            <img src="{{ asset('souqify-2/assets/images/hero-mobile.jpg') }}" alt="" class="lg:hidden absolute inset-0 h-full w-full object-cover" />
            <img src="{{ asset('souqify-2/assets/images/hero-desktop.jpg') }}" alt="" class="hidden lg:block absolute inset-0 h-full w-full object-cover" />
            <div class="absolute inset-0" style="background: rgba(0, 0, 0, 0.44)"></div>
            <div class="relative flex flex-col gap-[19px] lg:gap-[47px] items-center justify-center text-center lg:text-left w-full lg:w-[459px]">
              <h1 class="font-semibold text-[24px] lg:text-[60px] text-white tracking-[0.7px] leading-[1.05]">
                <span style="color: var(--color-souqify-teal)">{{ __('Explore') }}</span> {{ __('New Products') }}
              </h1>
              <a href="{{ route('tenant.storefront.best-selling') }}" class="flex h-[38px] lg:h-[95px] items-center justify-center rounded-[34px] lg:rounded-full w-full lg:w-[459px] px-[8px] cursor-pointer" style="background: var(--color-souqify-teal)">
                <span class="font-medium text-[14px] lg:text-[35px] text-white tracking-[0.5px]">{{ __('Shop Now') }}</span>
              </a>
            </div>
          </div>
        </div>
        <div class="swiper-slide">
          <div class="relative flex flex-col items-center lg:items-start justify-end h-[280px] lg:h-[543px] px-[18px] lg:px-[61px] py-[22px] lg:py-[49px] overflow-hidden">
            <img src="{{ asset('souqify-2/assets/images/hero-mobile.jpg') }}" alt="" class="lg:hidden absolute inset-0 h-full w-full object-cover" />
            <img src="{{ asset('souqify-2/assets/images/hero-desktop.jpg') }}" alt="" class="hidden lg:block absolute inset-0 h-full w-full object-cover" />
            <div class="absolute inset-0" style="background: rgba(0, 0, 0, 0.44)"></div>
            <div class="relative flex flex-col gap-[19px] lg:gap-[47px] items-center justify-center text-center lg:text-left w-full lg:w-[459px]">
              <h1 class="font-semibold text-[24px] lg:text-[60px] text-white tracking-[0.7px] leading-[1.05]">
                {{ __('Flash Sale') }} <span style="color: var(--color-souqify-teal)">{{ __('Live Now') }}</span>
              </h1>
              <a href="{{ route('tenant.storefront.best-selling') }}" class="flex h-[38px] lg:h-[95px] items-center justify-center rounded-[34px] lg:rounded-full w-full lg:w-[459px] px-[8px] cursor-pointer" style="background: var(--color-souqify-teal)">
                <span class="font-medium text-[14px] lg:text-[35px] text-white tracking-[0.5px]">{{ __('Shop Deals') }}</span>
              </a>
            </div>
          </div>
        </div>
        <div class="swiper-slide">
          <div class="relative flex flex-col items-center lg:items-start justify-end h-[280px] lg:h-[543px] px-[18px] lg:px-[61px] py-[22px] lg:py-[49px] overflow-hidden">
            <img src="{{ asset('souqify-2/assets/images/hero-mobile.jpg') }}" alt="" class="lg:hidden absolute inset-0 h-full w-full object-cover" />
            <img src="{{ asset('souqify-2/assets/images/hero-desktop.jpg') }}" alt="" class="hidden lg:block absolute inset-0 h-full w-full object-cover" />
            <div class="absolute inset-0" style="background: rgba(0, 0, 0, 0.44)"></div>
            <div class="relative flex flex-col gap-[19px] lg:gap-[47px] items-center justify-center text-center lg:text-left w-full lg:w-[459px]">
              <h1 class="font-semibold text-[24px] lg:text-[60px] text-white tracking-[0.7px] leading-[1.05]">
                {{ __('Free Shipping') }} <span style="color: var(--color-souqify-teal)">{{ __('Worldwide') }}</span>
              </h1>
              <a href="{{ route('tenant.storefront.best-selling') }}" class="flex h-[38px] lg:h-[95px] items-center justify-center rounded-[34px] lg:rounded-full w-full lg:w-[459px] px-[8px] cursor-pointer" style="background: var(--color-souqify-teal)">
                <span class="font-medium text-[14px] lg:text-[35px] text-white tracking-[0.5px]">{{ __('Start Shopping') }}</span>
              </a>
            </div>
          </div>
        </div>
      @endforelse
    </div>
    <div class="hero-pagination"></div>
  </div>
</section>
