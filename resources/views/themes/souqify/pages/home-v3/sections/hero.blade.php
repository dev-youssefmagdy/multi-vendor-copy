@php
    $__heroBanners = collect($banners ?? [])->take(3)->values();

    // Design tokens (Figma – souqify v3 "Modern Edition" hero)
    $__slideCls   = 'relative flex flex-col items-center justify-center h-[280px] lg:h-[581px] px-[18px] lg:px-[61px] py-[22px] lg:py-[49px] overflow-hidden';
    $__overlayCls = 'absolute inset-0';
    $__overlayBg  = 'background: rgba(0, 0, 0, 0.67)';
    $__contentCls = 'relative flex flex-col items-center justify-center text-center gap-[19px] lg:gap-[48px] w-full lg:w-[633px]';
    $__titleCls   = 'font-bold text-[24px] lg:text-[60px] text-white tracking-[0.7px] lg:tracking-[1.72px] leading-[1.1] lg:leading-[76px]';
    $__ctaCls     = 'flex h-[38px] lg:h-[95px] items-center justify-center rounded-[34px] lg:rounded-[85px] w-full lg:w-[459px] px-[8px] lg:px-[20px] cursor-pointer';
    $__ctaTextCls = 'font-medium text-[14px] lg:text-[35px] text-white tracking-[0.5px] lg:tracking-[1.25px]';

    $__fallbackSlides = [
        ['lead' => __('Explore'), 'leadTeal' => true,  'rest' => __('New Products'),  'cta' => __('Shop Now')],
        ['lead' => __('Flash Sale'), 'leadTeal' => false, 'rest' => __('Live Now'),   'cta' => __('Shop Deals')],
        ['lead' => __('Free Shipping'), 'leadTeal' => false, 'rest' => __('Worldwide'), 'cta' => __('Start Shopping')],
    ];
@endphp
<!-- ============ HERO ============ -->
<section class="relative">
  <div class="sqv-hero-static">
    <div class="sqv-hero-static__inner">
      @forelse ($__heroBanners as $banner)
        <div class="swiper-slide">
          <div class="{{ $__slideCls }}">
            <img src="{{ $banner->image_path ?? asset('souqify-2/assets/images/hero-mobile.jpg') }}" alt="{{ $banner->title ?? $storeName }}" class="lg:hidden absolute inset-0 h-full w-full object-cover" />
            <img src="{{ $banner->image_path ?? asset('souqify-2/assets/images/hero-desktop.jpg') }}" alt="{{ $banner->title ?? $storeName }}" class="hidden lg:block absolute inset-0 h-full w-full object-cover" />
            <div class="{{ $__overlayCls }}" style="{{ $__overlayBg }}"></div>
            <div class="{{ $__contentCls }}">
              <h1 class="{{ $__titleCls }}">
                {!! $banner->title ? nl2br(e($banner->title)) : '<span style="color: var(--color-souqify-teal)">' . __('Explore') . '</span> ' . __('New Products') !!}
              </h1>
              <a href="{{ $banner->link ?? route('tenant.storefront.best-selling') }}" class="{{ $__ctaCls }}" style="background: var(--color-souqify-teal)">
                <span class="{{ $__ctaTextCls }}">{{ __('Shop Now') }}</span>
              </a>
            </div>
          </div>
        </div>
      @empty
        @foreach ($__fallbackSlides as $__slide)
          <div class="swiper-slide">
            <div class="{{ $__slideCls }}">
              <img src="{{ asset('souqify-2/assets/images/hero-mobile.jpg') }}" alt="" class="lg:hidden absolute inset-0 h-full w-full object-cover" />
              <img src="{{ asset('souqify-2/assets/images/hero-desktop.jpg') }}" alt="" class="hidden lg:block absolute inset-0 h-full w-full object-cover" />
              <div class="{{ $__overlayCls }}" style="{{ $__overlayBg }}"></div>
              <div class="{{ $__contentCls }}">
                <h1 class="{{ $__titleCls }}">
                  @if ($__slide['leadTeal'])
                    <span style="color: var(--color-souqify-teal)">{{ $__slide['lead'] }}</span> {{ $__slide['rest'] }}
                  @else
                    {{ $__slide['lead'] }} <span style="color: var(--color-souqify-teal)">{{ $__slide['rest'] }}</span>
                  @endif
                </h1>
                <a href="{{ route('tenant.storefront.best-selling') }}" class="{{ $__ctaCls }}" style="background: var(--color-souqify-teal)">
                  <span class="{{ $__ctaTextCls }}">{{ $__slide['cta'] }}</span>
                </a>
              </div>
            </div>
          </div>
        @endforeach
      @endforelse
    </div>
  </div>
</section>

<style>
    /* Hero shows one static banner - the carousel was removed, so the extra
       banners stay in the markup but only the first one renders. */
    .sqv-hero-static { position: relative; width: 100%; overflow: hidden; }
    .sqv-hero-static__inner { display: block; width: 100%; }
    .sqv-hero-static__inner > .swiper-slide { width: 100%; }
    .sqv-hero-static__inner > .swiper-slide:not(:first-child) { display: none; }
</style>
