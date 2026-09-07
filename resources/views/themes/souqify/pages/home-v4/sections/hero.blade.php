@php
    $__heroBanners = collect($banners ?? [])->take(3)->values();
    // Guarantee at least one slide so the markup below stays single-sourced.
    $__heroSlides = $__heroBanners->isNotEmpty() ? $__heroBanners : collect([null]);
@endphp

{{-- Figma: Frame 1984080398 (hero, 1440x629). All px values below are the exact
     Figma measurements expressed as clamp(min, <value>/1440 * 100vw, max) so the
     section reproduces the comp at 1440px and scales down proportionally. --}}
<style>
    .sqv4-hero__slide {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 20px 16px;
        gap: 8px;
        position: relative;
        overflow: hidden;
        /* Mobile comp keeps roughly a 390 x 279 frame (0.715 of the width). */
        height: clamp(240px, 71.5vw, 300px);
        background: var(--color-bg-main);
    }
    .sqv4-hero__media {
        position: absolute;
        right: 0;
        bottom: 0;
        /* The model sits fully inside the frame on mobile - nothing cropped. */
        height: 92%;
        max-width: 50%;
        width: auto;
        object-fit: contain;
        object-position: right bottom;
    }
    .sqv4-hero__content {
        position: relative;
        z-index: 10;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 0;
        gap: 12px;
        width: 100%;
        /* Text column clears the model on the right. */
        max-width: 55%;
    }
    .sqv4-hero__title {
        font-family: 'Outfit', sans-serif;
        font-style: normal;
        font-weight: 700;
        font-size: clamp(20px, 5.5vw, 28px);
        line-height: 1.2523;      /* 136 / 108.59 */
        letter-spacing: 0.8px;
        color: var(--color-text-primary);
        margin: 0;
    }
    .sqv4-hero__title .sqv4-hero__accent { color: var(--color-brand-green); }
    .sqv4-hero__cta {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        padding: 8px;
        gap: 8px;
        /* Pill spans ~52% of the mobile frame. */
        width: clamp(150px, 52vw, 205px);
        height: clamp(40px, 12.8vw, 50px);
        background: var(--color-brand-green);
        border-radius: 68.7432px;
        cursor: pointer;
    }
    .sqv4-hero__cta-label {
        font-family: 'Outfit', sans-serif;
        font-style: normal;
        font-weight: 500;
        font-size: clamp(13px, 3.6vw, 16px);
        line-height: 1.8017;      /* 51 / 28.306 */
        letter-spacing: 0.55px;
        color: #FFFFFF;
        white-space: nowrap;
    }

    @media (min-width: 1024px) {
        .sqv4-hero__slide {
            /* 629px tall, 24px 56px padding on the 1440 canvas */
            height: clamp(420px, 43.681vw, 629px);
            padding: 24px clamp(24px, 3.889vw, 56px);
        }
        .sqv4-hero__media {
            right: clamp(0px, 2.778vw, 40px);
            height: 115%;
            max-width: none;
        }
        .sqv4-hero__content {
            /* 828px wide, 38px gap */
            max-width: clamp(420px, 57.5vw, 828px);
            gap: clamp(20px, 2.639vw, 38px);
        }
        .sqv4-hero__title {
            /* 108.59px / 3.11335px tracking */
            font-size: clamp(52px, 7.541vw, 108.59px);
            letter-spacing: clamp(1.5px, 0.2162vw, 3.11335px);
        }
        .sqv4-hero__cta {
            /* 370 x 76.83, 16.1749px padding + gap */
            width: clamp(240px, 25.694vw, 370px);
            height: clamp(52px, 5.335vw, 76.83px);
            padding: clamp(10px, 1.123vw, 16.1749px);
            gap: clamp(10px, 1.123vw, 16.17px);
        }
        .sqv4-hero__cta-label {
            /* 28.306px / 1.01093px tracking */
            font-size: clamp(18px, 1.966vw, 28.306px);
            letter-spacing: clamp(0.6px, 0.0702vw, 1.01093px);
        }
    }
</style>

<!-- ============ HERO ============ -->
<section class="relative">
  <div class="sqv-hero-static">
    <div class="sqv-hero-static__inner">
      @foreach ($__heroSlides as $banner)
        <div class="swiper-slide">
          <div class="sqv4-hero__slide">
            <img
              src="{{ $banner->image_path ?? asset('souqify-3/assets/images/hero-shopping-woman.png') }}"
              alt="{{ $banner->title ?? ($storeName ?? '') }}"
              class="sqv4-hero__media" />
            <div class="sqv4-hero__content">
              <h1 class="sqv4-hero__title">
                {!! $banner && $banner->title
                    ? nl2br(e($banner->title))
                    : '<span class="sqv4-hero__accent">' . __('Explore') . '</span> ' . __('New Products') !!}
              </h1>
              <a href="{{ ($banner->link ?? null) ?: route('tenant.storefront.best-selling') }}" class="sqv4-hero__cta">
                <span class="sqv4-hero__cta-label">{{ __('Shop Now') }}</span>
              </a>
            </div>
          </div>
        </div>
      @endforeach
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
