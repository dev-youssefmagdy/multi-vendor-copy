@php
    $__heroBanners = collect($banners ?? [])->take(3)->values();
@endphp
<!-- ============ HERO ============ -->
<section class="relative bg-white">
    <div class="swiper hero-swiper">
        <div class="swiper-wrapper">
            @forelse ($__heroBanners as $banner)
                <div class="swiper-slide">
                    <div class="flex flex-col-reverse lg:flex-row items-center justify-between px-[18px] lg:px-[56px] py-[16px] lg:py-[24px] gap-[8px] lg:gap-[24px]">
                        <div class="flex flex-col gap-[16px] lg:gap-[37px] items-start justify-center lg:w-1/2">
                            <h1 class="font-semibold text-[28px] lg:text-[64px] tracking-[0.7px] lg:tracking-[2.5px] leading-[1.05]" style="color:var(--color-text-primary)">
                                {!! $banner->title ? nl2br(e($banner->title)) : '<span class="font-bold" style="color:var(--color-brand-pink)">' . __('Explore') . '</span> ' . __('New Products') !!}
                            </h1>
                            <a href="{{ $banner->link ?? route('tenant.storefront.best-selling') }}" class="flex h-[38px] lg:h-[74px] items-center justify-center rounded-[34px] lg:rounded-[66px] w-full lg:w-[357px] px-[8px] cursor-pointer" style="background:var(--color-brand-pink)">
                                <span class="font-medium text-[14px] lg:text-[27px] text-white tracking-[0.5px]">{{ __('Shop Now') }}</span>
                            </a>
                        </div>
                        <img src="{{ $banner->image_path ?? asset('souqify-5/assets/images/hero-photo.png') }}" alt="{{ $banner->title ?? $storeName }}" class="w-[170px] lg:w-auto lg:h-[420px] object-contain" />
                    </div>
                </div>
            @empty
                <div class="swiper-slide">
                    <div class="flex flex-col-reverse lg:flex-row items-center justify-between px-[18px] lg:px-[56px] py-[16px] lg:py-[24px] gap-[8px] lg:gap-[24px]">
                        <div class="flex flex-col gap-[16px] lg:gap-[37px] items-start justify-center lg:w-1/2">
                            <h1 class="font-semibold text-[28px] lg:text-[64px] tracking-[0.7px] lg:tracking-[2.5px] leading-[1.05]" style="color:var(--color-text-primary)">
                                <span class="font-bold" style="color:var(--color-brand-pink)">{{ __('Explore') }}</span> {{ __('New Products') }}
                            </h1>
                            <a href="{{ route('tenant.storefront.best-selling') }}" class="flex h-[38px] lg:h-[74px] items-center justify-center rounded-[34px] lg:rounded-[66px] w-full lg:w-[357px] px-[8px] cursor-pointer" style="background:var(--color-brand-pink)">
                                <span class="font-medium text-[14px] lg:text-[27px] text-white tracking-[0.5px]">{{ __('Shop Now') }}</span>
                            </a>
                        </div>
                        <img src="{{ asset('souqify-5/assets/images/hero-photo.png') }}" alt="" class="w-[170px] lg:w-auto lg:h-[420px] object-contain" />
                    </div>
                </div>
            @endforelse
        </div>
        <div class="hero-pagination"></div>
    </div>
</section>
