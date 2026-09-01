@php
    $__heroBanners = collect($banners ?? [])->take(3)->values();
@endphp
<!-- ============ HERO ============ -->
<section class="relative">
  <div class="swiper hero-swiper">
    <div class="swiper-wrapper">
      @forelse ($__heroBanners as $banner)
        <div class="swiper-slide">
          <div class="relative flex flex-col items-start justify-center h-[220px] lg:h-[480px] px-[16px] lg:px-[56px] overflow-hidden" style="background:var(--color-bg-main)">
            <img src="{{ $banner->image_path ?? asset('souqify-3/assets/images/hero-shopping-woman.png') }}" alt="{{ $banner->title ?? $storeName }}" class="absolute right-[-10px] lg:right-[40px] bottom-0 h-full lg:h-[115%] w-auto object-contain object-bottom" />
            <div class="relative flex flex-col gap-[12px] lg:gap-[28px] max-w-[170px] lg:max-w-[560px] z-10">
              <h1 class="font-semibold lg:font-bold text-[24px] lg:text-[58px] leading-[1.1] tracking-[0.5px]">
                {!! $banner->title ? nl2br(e($banner->title)) : '<span style="color:var(--color-brand-green)">' . __('Explore') . '</span> ' . __('New Products') !!}
              </h1>
              <a href="{{ $banner->link ?? route('tenant.storefront.best-selling') }}" class="flex h-[38px] lg:h-[68px] items-center justify-center rounded-full px-[24px] lg:px-[44px] w-fit cursor-pointer" style="background:var(--color-brand-green)">
                <span class="font-medium text-[14px] lg:text-[22px] text-white tracking-[0.5px]">{{ __('Shop Now') }}</span>
              </a>
            </div>
          </div>
        </div>
      @empty
        <div class="swiper-slide">
          <div class="relative flex flex-col items-start justify-center h-[220px] lg:h-[480px] px-[16px] lg:px-[56px] overflow-hidden" style="background:var(--color-bg-main)">
            <img src="{{ asset('souqify-3/assets/images/hero-shopping-woman.png') }}" alt="" class="absolute right-[-10px] lg:right-[40px] bottom-0 h-full lg:h-[115%] w-auto object-contain object-bottom" />
            <div class="relative flex flex-col gap-[12px] lg:gap-[28px] max-w-[170px] lg:max-w-[560px] z-10">
              <h1 class="font-semibold lg:font-bold text-[24px] lg:text-[58px] leading-[1.1] tracking-[0.5px]">
                <span style="color:var(--color-brand-green)">{{ __('Explore') }}</span> {{ __('New Products') }}
              </h1>
              <a href="{{ route('tenant.storefront.best-selling') }}" class="flex h-[38px] lg:h-[68px] items-center justify-center rounded-full px-[24px] lg:px-[44px] w-fit cursor-pointer" style="background:var(--color-brand-green)">
                <span class="font-medium text-[14px] lg:text-[22px] text-white tracking-[0.5px]">{{ __('Shop Now') }}</span>
              </a>
            </div>
          </div>
        </div>
      @endforelse
    </div>
    <div class="hero-pagination"></div>
  </div>
</section>
