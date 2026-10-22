@php
    $__heroBanners = collect($banners ?? [])->take(8)->values();

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
<section class="relative mb-[16px] lg:mb-[48px]" wire:ignore>
  <div class="swiper sqv3-hero-swiper">
    <div class="swiper-wrapper">
      @forelse ($__heroBanners as $banner)
        <div class="swiper-slide">
          <div class="{{ $__slideCls }}">
            @if (!empty($banner?->image_path))
            <img src="{{ $banner->image_path }}" onerror="this.remove()" alt="{{ $banner->translationValue('title') ?? $storeName }}" class="lg:hidden absolute inset-0 h-full w-full object-cover" />
            @endif
            @if (!empty($banner?->image_path))
            <img src="{{ $banner->image_path }}" onerror="this.remove()" alt="{{ $banner->translationValue('title') ?? $storeName }}" class="hidden lg:block absolute inset-0 h-full w-full object-cover" />
            @endif
            <div class="{{ $__overlayCls }}" style="{{ $__overlayBg }}"></div>
            <div class="{{ $__contentCls }}">
              <h1 class="{{ $__titleCls }}">
                {!! $banner->translationValue('title') ? nl2br(e($banner->translationValue('title'))) : '<span style="color: var(--color-souqify-teal)">' . __('Explore') . '</span> ' . __('New Products') !!}
              </h1>
@if ($__sub = $banner->translationValue('subtitle'))
<p style="margin:0;font-size:clamp(14px,1.4vw,22px);line-height:1.4;color:#fff">{{ $__sub }}</p>
@endif

              <a href="{{ $banner->url ?? route('tenant.storefront.best-selling') }}" class="{{ $__ctaCls }}" style="background: var(--color-souqify-teal)">
                <span class="{{ $__ctaTextCls }}">{{ $banner->translationValue('button_text') ?: __('Shop Now') }}</span>
              </a>
            </div>
          </div>
        </div>
      @empty
        @foreach (array_slice($__fallbackSlides, 0, 1) as $__slide)
          <div class="swiper-slide">
            <div class="{{ $__slideCls }}">
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
    @if ($__heroBanners->count() > 1)
      <div class="swiper-pagination sqv3-hero-pagination"></div>
    @endif
  </div>
</section>

<style>
    .sqv3-hero-swiper { position: relative; width: 100%; overflow: hidden; }
    .sqv3-hero-swiper .swiper-slide { width: 100%; }
    .sqv3-hero-pagination { position: absolute; bottom: 12px; z-index: 20; }
    .sqv3-hero-pagination .swiper-pagination-bullet { background: #fff; opacity: .6; }
    .sqv3-hero-pagination .swiper-pagination-bullet-active { background: var(--color-souqify-teal); opacity: 1; }
</style>

<script>
(function () {
  function mountHeroV3() {
    var el = document.querySelector('.sqv3-hero-swiper');
    if (!el || el.swiper || typeof Swiper === 'undefined') return;
    var multi = el.querySelectorAll('.swiper-slide').length > 1;
    new Swiper(el, {
      loop: multi,
      allowTouchMove: multi,
      speed: 600,
      autoplay: multi ? { delay: 5000, disableOnInteraction: false, pauseOnMouseEnter: true } : false,
      pagination: multi ? { el: el.querySelector('.sqv3-hero-pagination'), clickable: true } : undefined,
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountHeroV3);
  } else {
    mountHeroV3();
  }
  window.addEventListener('load', mountHeroV3);
  document.addEventListener('livewire:navigated', mountHeroV3);
})();
</script>
