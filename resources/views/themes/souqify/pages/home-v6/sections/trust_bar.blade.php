{{-- Figma: "Featured" bar (1439x76.2 on the 1440 canvas), #FFD428.
     The four feature blocks measure 1368px of content against the 1328px the
     56px padding leaves, so the strip runs as a Swiper carousel at 3.5 slides
     per view instead of clipping. Sizing values are the Figma measurements as
     clamp(min, <value>/1440*100vw, max). --}}
<style>
    /* Frame 1984080268: the phone strip is pink with white type and a white
       hairline between features. Desktop keeps the yellow Featured bar. */
    .sqv6-trust {
        background: var(--color-brand-pink, #FF1A90);
        --sqv6-trust-title: #FFFFFF;
        --sqv6-trust-sub: rgba(255, 255, 255, 0.85);
    }
    .sqv6-trust__viewport {
        /* 13.0987px 56px padding on the 1440 canvas */
        padding: 4px 18px;
        overflow: hidden;
    }
    @media (prefers-reduced-motion: reduce) {
        .sqv6-trust .swiper-wrapper { transition-duration: 0ms !important; }
    }
    /* Feature: 39.3 icon + info, 26.2px apart, 50px tall. */
    .sqv6-trust__slide {
        display: flex;
        flex-direction: row;
        /* Phones show one feature at a time, so it is centred in its slide. */
        box-sizing: border-box;
        /* Centred in its own slide, with room on both sides so the text never
           touches the divider sitting on the slide's right edge. */
        justify-content: center;
        align-items: center;
        padding: 6.23469px 10px;
        gap: 6.23px;
        height: 31.17px;
        /* The divider is drawn inside the slide's own box, so a long label can
           never run over it - the label ellipsises first. */
        position: relative;
        overflow: hidden;
        border-right: 0;
    }
    .sqv6-trust__icon {
        /* 18.7px, painted white to match the comp's vectors. */
        width: 18.7px;
        height: 18.7px;
        flex: none;
        filter: brightness(0) invert(1);
    }
    .sqv6-trust__info {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        flex: 0 1 auto;
        min-width: 0;
    }
    .sqv6-trust__slide::after {
        content: '';
        position: absolute;
        right: 0;
        top: 15%;
        bottom: 15%;
        width: 1px;
        background: #FFFFFF;
    }
    /* The divider belongs between features, not after the last visible one. */
    .sqv6-trust__slide:last-child::after { display: none; }
    /* Phones carry the title only. */
    .sqv6-trust__sub { display: none; }
    .sqv6-trust__title,
    .sqv6-trust__sub {
        font-family: 'Outfit', sans-serif;
        font-style: normal;
        /* 19.648px / 25px line-height / 0.818667px tracking */
        /* 9.35204px / 12px line-height / 0.389668px tracking */
        font-size: 9.35204px;
        line-height: 1.2831;
        letter-spacing: 0.389668px;
        white-space: nowrap;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sqv6-trust__title { font-weight: 600; color: var(--sqv6-trust-title); }
    .sqv6-trust__sub   { font-weight: 400; color: var(--sqv6-trust-sub); }

    @media (min-width: 1024px) {
        /* Continuous marquee: linear easing turns Swiper's stepped autoplay into
           an even scroll. Desktop only - mobile is hand-swiped. */
        .sqv6-trust {
            background: var(--color-accent-yellow, #FFD428);
            box-shadow: 0px 13.0987px 65.4933px rgba(0, 38, 3, 0.08);
            --sqv6-trust-title: #070707;
            --sqv6-trust-sub: #444444;
        }
        .sqv6-trust .swiper-wrapper { transition-timing-function: linear !important; }
        .sqv6-trust__viewport {
            padding: clamp(10px, 0.9096vw, 13.0987px) clamp(24px, 3.889vw, 56px);
        }
        .sqv6-trust__slide {
            /* Desktop packs several features per row, so they read left-aligned. */
            justify-content: flex-start;
            padding: 0;
            gap: clamp(14px, 1.8194vw, 26.2px);
            height: 50px;
            overflow: visible;
        }
        .sqv6-trust__slide::after { display: none; }
        .sqv6-trust__icon {
            width: clamp(28px, 2.7292vw, 39.3px);
            height: clamp(28px, 2.7292vw, 39.3px);
            filter: none;
        }
        .sqv6-trust__info { height: 50px; }
        .sqv6-trust__sub { display: block; }
        .sqv6-trust__title,
        .sqv6-trust__sub {
            font-size: clamp(14px, 1.3644vw, 19.648px);
            letter-spacing: clamp(0.55px, 0.0569vw, 0.818667px);
        }
    }
</style>

@php
    // Swiper's loop needs at least 2x slidesPerView to run without gaps, so the
    // four Figma features are repeated to fill the marquee. aria-hidden on the
    // repeats keeps screen readers from announcing each feature twice.
    $__trustFeatures = [
        ['icon' => 'feature-truck.svg',      'title' => __('Free Shipping'),         'sub' => __('Free shipping on all your order')],
        ['icon' => 'feature-headphones.svg', 'title' => __('Customer Support 24/7'), 'sub' => __('Instant access to Support')],
        ['icon' => 'feature-secure.svg',     'title' => __('100% Secure Payment'),   'sub' => __('We ensure your money is save')],
        ['icon' => 'feature-package.svg',    'title' => __('Money-Back Guarantee'),  'sub' => __('30 Days Money-Back Guarantee')],
    ];
    $__trustSlides = array_merge($__trustFeatures, $__trustFeatures);
@endphp

<!-- ============ FEATURED STRIP ============ -->
<section class="sqv6-trust">
  <div class="swiper trust-swiper sqv6-trust__viewport">
    <div class="swiper-wrapper">
      @foreach ($__trustSlides as $__index => $__feature)
        <div class="swiper-slide sqv6-trust__slide" @if ($__index >= count($__trustFeatures)) aria-hidden="true" @endif>
          <img src="{{ asset('souqify-5/assets/icons/' . $__feature['icon']) }}" alt="" class="sqv6-trust__icon" />
          <div class="sqv6-trust__info">
            <span class="sqv6-trust__title">{{ $__feature['title'] }}</span>
            <span class="sqv6-trust__sub">{{ $__feature['sub'] }}</span>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>
