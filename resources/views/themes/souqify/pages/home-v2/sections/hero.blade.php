@php
    $__heroBanners = collect($banners ?? [])->take(3)->values();

    // The heading reads as a purple first word followed by the rest in black, so
    // a banner title from the database is split the same way the designed copy
    // is.
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

{{-- Figma: Frame 1984080400 (1440 x 623) - white, 32px 56px padding. The copy
     column is 609 wide with a 38px gap; the cut-out photo is the frame's
     background, anchored right. Values are clamp(min, <value>/1440*100vw, max)
     off the 1440 canvas. --}}
<style>
    .sqv2-hero__slide {
        position: relative;
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 20px 16px;
        height: 200px;
        background: #FFFFFF;
        overflow: hidden;
    }
    /* Frame 1984080198: the copy column. */
    .sqv2-hero__copy {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        gap: 14px;
        max-width: 54%;
    }
    .sqv2-hero__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: 24px;
        line-height: 1.3;               /* 144 / 96 */
        letter-spacing: 0.5px;
        margin: 0;
    }
    .sqv2-hero__title-lead { color: var(--color-brand-purple, #8B03BD); }
    .sqv2-hero__title-rest { color: var(--color-text-primary, #121212); }
    /* Frame 1984080196: 374 x 79, radius 70. */
    .sqv2-hero__cta {
        /* Figma's 79 height is the outer box and the 16px padding sits inside
           it, so the button measures border-box. */
        box-sizing: border-box;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 16px;
        padding: 16px;
        /* Sized directly rather than width:100% - the copy column is
           shrink-to-fit, so a percentage would resolve against the heading. */
        width: 150px;
        height: 42px;
        background: var(--color-brand-purple, #8B03BD);
        border-radius: 70px;
        text-decoration: none;
    }
    .sqv2-hero__cta-label {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 14px;
        line-height: 1.4;               /* 52 / 29.3067 */
        letter-spacing: 0.6px;
        color: #FFFFFF;
        white-space: nowrap;
    }
    .sqv2-hero__photo {
        position: absolute;
        right: 0;
        bottom: 0;
        height: 100%;
        width: auto;
        max-width: 50%;
        object-fit: contain;
        object-position: right bottom;
    }

    @media (min-width: 1024px) {
        .sqv2-hero__slide {
            /* 623 tall, 32px 56px padding */
            height: clamp(430px, 43.264vw, 623px);
            padding: clamp(24px, 2.222vw, 32px) clamp(24px, 3.889vw, 56px);
        }
        .sqv2-hero__copy {
            /* 609 wide, 38px gap */
            max-width: clamp(440px, 42.292vw, 609px);
            gap: clamp(20px, 2.639vw, 38px);
        }
        .sqv2-hero__title {
            /* 96px / 144px line-height, weight 700 */
            font-size: clamp(48px, 6.667vw, 96px);
            letter-spacing: clamp(0.5px, 0.0694vw, 1px);
        }
        .sqv2-hero__cta {
            width: clamp(260px, 25.972vw, 374px);
            height: clamp(56px, 5.486vw, 79px);
        }
        .sqv2-hero__cta-label {
            /* 29.3067px */
            font-size: clamp(18px, 2.0352vw, 29.3067px);
        }
        .sqv2-hero__photo {
            /* The comp's cut-out runs the full height of the 623px frame and
               takes a bit over half its width. */
            max-width: 56%;
            height: 100%;
        }
    }
</style>

<!-- ============ HERO ============ -->
<section class="relative bg-white">
  <div class="sqv-hero-static">
    <div class="sqv-hero-static__inner">
      @forelse ($__heroBanners as $banner)
        <div class="swiper-slide">
          <div class="sqv2-hero__slide">
            @php
              $__t = $banner->title
                  ? $__splitTitle($banner->title)
                  : ['lead' => __('Explore'), 'rest' => __('New Products')];
            @endphp
            <div class="sqv2-hero__copy">
              <h1 class="sqv2-hero__title">
                <span class="sqv2-hero__title-lead">{{ $__t['lead'] }}</span>
                @if ($__t['rest'] !== '')
                  <span class="sqv2-hero__title-rest">{!! nl2br(e($__t['rest'])) !!}</span>
                @endif
              </h1>
              <a href="{{ $banner->link ?? route('tenant.storefront.best-selling') }}" class="sqv2-hero__cta">
                <span class="sqv2-hero__cta-label">{{ __('Shop Now') }}</span>
              </a>
            </div>
            <img src="{{ $banner->image_path ?? asset('souqify-1/assets/images/hero-shopping-couple.png') }}" alt="{{ $banner->title ?? $storeName }}" class="sqv2-hero__photo" />
          </div>
        </div>
      @empty
        @foreach ($__fallbackSlides as $__slide)
          <div class="swiper-slide">
            <div class="sqv2-hero__slide">
              <div class="sqv2-hero__copy">
                <h1 class="sqv2-hero__title">
                  <span class="sqv2-hero__title-lead">{{ $__slide['lead'] }}</span>
                  <span class="sqv2-hero__title-rest">{{ $__slide['rest'] }}</span>
                </h1>
                <a href="{{ route('tenant.storefront.best-selling') }}" class="sqv2-hero__cta">
                  <span class="sqv2-hero__cta-label">{{ $__slide['cta'] }}</span>
                </a>
              </div>
              <img src="{{ asset('souqify-1/assets/images/hero-shopping-couple.png') }}" alt="" class="sqv2-hero__photo" />
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
