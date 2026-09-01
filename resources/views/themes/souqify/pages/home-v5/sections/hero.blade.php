@php
    $__heroBanners = collect($banners ?? [])->take(3)->values();
@endphp
<!-- ============ HERO ============ -->
<section class="relative bg-white">
    <div class="swiper hero-swiper">
        <div class="swiper-wrapper">
            @forelse ($__heroBanners as $banner)
                <div class="swiper-slide">
                    <div class="relative flex items-center justify-between h-[200px] lg:h-[560px] px-[16px] lg:px-[56px] overflow-hidden bg-white">
                        <div class="relative z-10 flex flex-col gap-[12px] lg:gap-[32px] max-w-[56%] lg:max-w-[620px]">
                            <h1 class="font-semibold lg:font-bold text-[24px] lg:text-[80px] leading-[1.08] tracking-[0.5px] lg:tracking-[2px]">
                                {!! $banner->title ? nl2br(e($banner->title)) : '<span style="color:var(--color-primary)">' . __('Explore') . '</span> <span style="color:var(--color-text-primary)">' . __('New Products') . '</span>' !!}
                            </h1>
                            <a href="{{ $banner->link ?? route('tenant.storefront.best-selling') }}" class="rounded-full h-[38px] lg:h-[68px] px-[24px] lg:px-[40px] flex items-center justify-center w-fit cursor-pointer" style="background:var(--color-primary)">
                                <span class="text-white font-medium text-[13px] lg:text-[24px] tracking-[0.5px]">{{ __('Shop Now') }}</span>
                            </a>
                        </div>
                        <img src="{{ $banner->image_path ?? asset('souqify-4/assets/images/hero-photo.png') }}" alt="{{ $banner->title ?? $storeName }}" class="absolute right-0 top-0 h-full w-auto max-w-[52%] lg:max-w-[46%] object-contain object-right" />
                    </div>
                </div>
            @empty
                <div class="swiper-slide">
                    <div class="relative flex items-center justify-between h-[200px] lg:h-[560px] px-[16px] lg:px-[56px] overflow-hidden bg-white">
                        <div class="relative z-10 flex flex-col gap-[12px] lg:gap-[32px] max-w-[56%] lg:max-w-[620px]">
                            <h1 class="font-semibold lg:font-bold text-[24px] lg:text-[80px] leading-[1.08] tracking-[0.5px] lg:tracking-[2px]"><span style="color:var(--color-primary)">{{ __('Explore') }}</span> <span style="color:var(--color-text-primary)">{{ __('New Products') }}</span></h1>
                            <a href="{{ route('tenant.storefront.best-selling') }}" class="rounded-full h-[38px] lg:h-[68px] px-[24px] lg:px-[40px] flex items-center justify-center w-fit cursor-pointer" style="background:var(--color-primary)">
                                <span class="text-white font-medium text-[13px] lg:text-[24px] tracking-[0.5px]">{{ __('Shop Now') }}</span>
                            </a>
                        </div>
                        <img src="{{ asset('souqify-4/assets/images/hero-photo.png') }}" alt="" class="absolute right-0 top-0 h-full w-auto max-w-[52%] lg:max-w-[46%] object-contain object-right" />
                    </div>
                </div>
            @endforelse
        </div>
        <div class="hero-pagination"></div>
    </div>
</section>
