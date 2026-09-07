@php
    $__heroBanners = collect($banners ?? [])->take(3)->values();

    // The heading always reads as an orange first word followed by the rest in
    // black, so a banner title from the database is split the same way the
    // designed copy is rather than printed as one block.
    $__splitTitle = function (?string $text) {
        $parts = preg_split('/\s+/', trim((string) $text), 2);

        return ['lead' => $parts[0] ?? '', 'rest' => $parts[1] ?? ''];
    };

    $__fallbackSlides = [
        ['lead' => __('Explore'),       'rest' => __('New Products'), 'cta' => __('Shop Now')],
        ['lead' => __('Flash Sale'),    'rest' => __('Live Now'),     'cta' => __('Shop Deals')],
        ['lead' => __('Free Shipping'), 'rest' => __('Worldwide'),    'cta' => __('Start Shopping')],
    ];
@endphp

{{-- Figma: Frame 1984080397 (1440x627) - white, 32px 56px padding. The copy
     column is 658.83 wide with a 37.03px gap; the model photo is the frame's
     background, anchored right. Values are clamp(min, <value>/1440*100vw, max)
     off the 1440 canvas. --}}
<style>
    .sqv5-hero__slide {
        position: relative;
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 16px;
        height: 200px;
        background: #FFFFFF;
        overflow: hidden;
    }
    .sqv5-hero__copy {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 0;
        gap: 12px;
        max-width: 56%;
    }
    .sqv5-hero__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: 24px;
        line-height: 1.2604;            /* 121 / 96 */
        letter-spacing: 0.5px;
        margin: 0;
    }
    .sqv5-hero__title-lead { color: var(--color-primary); }
    .sqv5-hero__title-rest { color: var(--color-text-primary); }
    .sqv5-hero__cta {
        /* Figma's 74.05 height is the outer box, and the 15.5897px padding sits
           inside it, so the button must measure border-box. */
        box-sizing: border-box;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15.59px;
        padding: 15.5897px;
        /* Sized directly rather than width:100% + max-width - the copy column is
           shrink-to-fit, so a percentage would resolve against whatever the
           heading happens to measure instead of the Figma button width. */
        width: 180px;
        height: 38px;
        background: var(--color-primary);
        border-radius: 66.2564px;
        text-decoration: none;
    }
    .sqv5-hero__cta-label {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 13px;
        line-height: 1.7961;            /* 49 / 27.2821 */
        letter-spacing: 0.974359px;
        color: #FFFFFF;
        white-space: nowrap;
    }
    .sqv5-hero__photo {
        position: absolute;
        right: 0;
        top: 0;
        height: 100%;
        width: auto;
        max-width: 52%;
        object-fit: contain;
        object-position: right;
    }

    @media (min-width: 1024px) {
        .sqv5-hero__slide {
            /* 627 tall, 32px 56px padding */
            height: clamp(420px, 43.542vw, 627px);
            padding: clamp(24px, 2.222vw, 32px) clamp(24px, 3.889vw, 56px);
        }
        .sqv5-hero__copy {
            /* 658.83 wide, 37.03px gap */
            max-width: clamp(440px, 45.752vw, 658.83px);
            gap: clamp(20px, 2.5715vw, 37.03px);
        }
        .sqv5-hero__title {
            /* 96px / 121px line-height, weight 700 */
            font-size: clamp(48px, 6.667vw, 96px);
            letter-spacing: clamp(1px, 0.172vw, 2.47726px);
        }
        .sqv5-hero__cta {
            /* 356.62 x 74.05 */
            width: clamp(240px, 24.765vw, 356.62px);
            height: clamp(52px, 5.142vw, 74.05px);
        }
        .sqv5-hero__cta-label {
            /* 27.2821px */
            font-size: clamp(18px, 1.8946vw, 27.2821px);
        }
        .sqv5-hero__photo {
            max-width: 46%;
        }
    }
</style>

<!-- ============ HERO ============ -->
<section class="relative bg-white">
  <div class="sqv-hero-static">
    <div class="sqv-hero-static__inner">
      @forelse ($__heroBanners as $banner)
        <div class="swiper-slide">
          <div class="sqv5-hero__slide">
            @php
              $__t = $banner->title
                  ? $__splitTitle($banner->title)
                  : ['lead' => __('Explore'), 'rest' => __('New Products')];
            @endphp
            <div class="sqv5-hero__copy">
              <h1 class="sqv5-hero__title">
                <span class="sqv5-hero__title-lead">{{ $__t['lead'] }}</span>
                @if ($__t['rest'] !== '')
                  <span class="sqv5-hero__title-rest">{!! nl2br(e($__t['rest'])) !!}</span>
                @endif
              </h1>
              <a href="{{ $banner->link ?? route('tenant.storefront.best-selling') }}" class="sqv5-hero__cta">
                <span class="sqv5-hero__cta-label">{{ __('Shop Now') }}</span>
              </a>
            </div>
            <img src="{{ $banner->image_path ?? asset('souqify-4/assets/images/hero-photo.png') }}" alt="{{ $banner->title ?? $storeName }}" class="sqv5-hero__photo" />
          </div>
        </div>
      @empty
        @foreach ($__fallbackSlides as $__slide)
          <div class="swiper-slide">
            <div class="sqv5-hero__slide">
              <div class="sqv5-hero__copy">
                <h1 class="sqv5-hero__title">
                  <span class="sqv5-hero__title-lead">{{ $__slide['lead'] }}</span>
                  <span class="sqv5-hero__title-rest">{{ $__slide['rest'] }}</span>
                </h1>
                <a href="{{ route('tenant.storefront.best-selling') }}" class="sqv5-hero__cta">
                  <span class="sqv5-hero__cta-label">{{ $__slide['cta'] }}</span>
                </a>
              </div>
              <img src="{{ asset('souqify-4/assets/images/hero-photo.png') }}" alt="" class="sqv5-hero__photo" />
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
