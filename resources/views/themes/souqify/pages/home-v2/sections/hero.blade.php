@php
    $__heroBanners = collect($banners ?? [])->take(3)->values();
@endphp
<!-- ============ HERO ============ -->
<section class="relative">
  <div class="swiper hero-swiper">
    <div class="swiper-wrapper">
      @forelse ($__heroBanners as $banner)
        <div class="swiper-slide">
          <div class="relative flex flex-col items-center lg:items-start justify-end h-[220px] lg:h-[543px] px-[18px] lg:px-[56px] py-[22px] lg:py-[32px] overflow-hidden bg-white">
            <img src="{{ $banner->image_path ?? asset('souqify-1/assets/images/hero-shopping-couple.png') }}" alt="{{ $banner->title ?? $storeName }}" class="absolute inset-0 h-full w-full object-cover" />
            <div class="absolute inset-0" style="background:linear-gradient(180deg, rgba(0,0,0,0) 55%, rgba(0,0,0,0.28) 100%)"></div>
            <div class="relative flex flex-col gap-[16px] lg:gap-[38px] items-start justify-center w-[220px] lg:w-[609px]">
              <h1 class="font-semibold lg:font-bold text-[26px] lg:text-[64px] tracking-[0.5px] leading-[1.1]" style="color:var(--color-text-primary)">{!! $banner->title ? nl2br(e($banner->title)) : __('Explore New Products') !!}</h1>
              <a href="{{ $banner->link ?? route('tenant.storefront.best-selling') }}" class="flex h-[40px] lg:h-[79px] items-center justify-center rounded-[70px] w-full max-w-[220px] lg:max-w-[374px] px-[16px] cursor-pointer" style="background:var(--color-brand-purple)">
                <span class="font-medium text-[14px] lg:text-[24px] text-white tracking-[0.5px]">{{ __('Shop Now') }}</span>
              </a>
            </div>
          </div>
        </div>
      @empty
        <div class="swiper-slide">
          <div class="relative flex flex-col items-center lg:items-start justify-end h-[220px] lg:h-[543px] px-[18px] lg:px-[56px] py-[22px] lg:py-[32px] overflow-hidden bg-white">
            <img src="{{ asset('souqify-1/assets/images/hero-shopping-couple.png') }}" alt="" class="absolute inset-0 h-full w-full object-cover" />
            <div class="absolute inset-0" style="background:linear-gradient(180deg, rgba(0,0,0,0) 55%, rgba(0,0,0,0.28) 100%)"></div>
            <div class="relative flex flex-col gap-[16px] lg:gap-[38px] items-start justify-center w-[220px] lg:w-[609px]">
              <h1 class="font-semibold lg:font-bold text-[26px] lg:text-[64px] tracking-[0.5px] leading-[1.1]" style="color:var(--color-text-primary)"><span style="color:var(--color-brand-purple)">{{ __('Explore') }}</span> {{ __('New Products') }}</h1>
              <a href="{{ route('tenant.storefront.best-selling') }}" class="flex h-[40px] lg:h-[79px] items-center justify-center rounded-[70px] w-full max-w-[220px] lg:max-w-[374px] px-[16px] cursor-pointer" style="background:var(--color-brand-purple)">
                <span class="font-medium text-[14px] lg:text-[24px] text-white tracking-[0.5px]">{{ __('Shop Now') }}</span>
              </a>
            </div>
          </div>
        </div>
      @endforelse
    </div>
    <div class="hero-pagination"></div>
  </div>
</section>
