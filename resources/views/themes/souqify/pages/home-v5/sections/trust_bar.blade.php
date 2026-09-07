{{-- Figma: "Featured" bar (1439x76.2 on the 1440 canvas), #FFD428.
     The four feature blocks are wider than the padded container (1525px of content
     vs 1328px available at 1440px), so the strip runs as a Swiper carousel at
     3.5 slides per view instead of clipping. Sizing values below are the exact Figma
     measurements, expressed as clamp(min, <value>/1440*100vw, max). --}}
<style>
    .sqv5-trust {
        background: var(--color-accent-yellow);
        box-shadow: 0px 13.0987px 65.4933px rgba(0, 38, 3, 0.08);
        --sqv5-trust-title: #070707;
        --sqv5-trust-sub: #444444;
    }
    .sqv5-trust__viewport {
        /* 13.0987px 56px padding on the 1440 canvas */
        padding: 13.0987px 16px;
        overflow: hidden;
    }
    @media (prefers-reduced-motion: reduce) {
        .sqv5-trust .swiper-wrapper { transition-duration: 0ms !important; }
    }
    .sqv5-trust__slide {
        display: flex;
        flex-direction: row;
        /* Mobile shows one feature at a time, so centre it in the slide. */
        justify-content: center;
        align-items: center;
        padding: 0;
        /* 26.2px gap between icon and info */
        gap: 12px;
        height: 50px;
    }
    .sqv5-trust__icon {
        /* 39.3 x 39.3 */
        width: 28px;
        height: 28px;
        flex: none;
    }
    .sqv5-trust__info {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 0;
        min-width: 0;
    }
    /* Mobile shows one feature per slide, title only. */
    .sqv5-trust__sub {
        display: none;
    }
    .sqv5-trust__title,
    .sqv5-trust__sub {
        font-family: 'Outfit', sans-serif;
        font-style: normal;
        /* 19.648px / 25px line-height / 0.818667px tracking */
        font-size: 13px;
        line-height: 1.2724;
        letter-spacing: 0.55px;
        white-space: nowrap;
    }
    .sqv5-trust__title { font-weight: 600; color: var(--sqv5-trust-title); }
    .sqv5-trust__sub   { font-weight: 400; color: var(--sqv5-trust-sub); }

    @media (min-width: 1024px) {
        /* Continuous marquee: linear easing turns Swiper's stepped autoplay into
           an even, uninterrupted scroll. Desktop only - mobile is hand-swiped. */
        .sqv5-trust .swiper-wrapper {
            transition-timing-function: linear !important;
        }
        .sqv5-trust__viewport {
            padding: clamp(10px, 0.9096vw, 13.0987px) clamp(24px, 3.889vw, 56px);
        }
        .sqv5-trust__slide {
            /* Desktop packs several features per row, so they read left-aligned. */
            justify-content: flex-start;
            gap: clamp(14px, 1.8194vw, 26.2px);
        }
        .sqv5-trust__icon {
            width: clamp(28px, 2.7292vw, 39.3px);
            height: clamp(28px, 2.7292vw, 39.3px);
        }
        .sqv5-trust__info { height: 50px; }
        .sqv5-trust__sub { display: block; }
        .sqv5-trust__title,
        .sqv5-trust__sub {
            font-size: clamp(14px, 1.3644vw, 19.648px);
            letter-spacing: clamp(0.55px, 0.0569vw, 0.818667px);
        }
    }
</style>

@php
    // Swiper's loop needs at least 2x slidesPerView to run without gaps, so the four
    // Figma features are repeated to fill the marquee. aria-hidden on the repeats
    // keeps screen readers from announcing each feature twice.
    $__trustFeatures = [
        ['icon' => 'feature-truck.svg',      'title' => __('Free Shipping'),         'sub' => __('Free shipping on all your order')],
        ['icon' => 'feature-headphones.svg', 'title' => __('Customer Support 24/7'), 'sub' => __('Instant access to Support')],
        ['icon' => 'feature-bag.svg',        'title' => __('100% Secure Payment'),   'sub' => __('We ensure your money is save')],
        ['icon' => 'feature-package.svg',    'title' => __('Money-Back Guarantee'),  'sub' => __('30 Days Money-Back Guarantee')],
    ];
    $__trustSlides = array_merge($__trustFeatures, $__trustFeatures);
@endphp

<!-- ============ FEATURED STRIP ============ -->
<section class="sqv5-trust">
  <div class="swiper trust-swiper sqv5-trust__viewport">
    <div class="swiper-wrapper">
      @foreach ($__trustSlides as $__index => $__feature)
        <div class="swiper-slide sqv5-trust__slide" @if ($__index >= count($__trustFeatures)) aria-hidden="true" @endif>
          <img src="{{ asset('souqify-4/assets/icons/' . $__feature['icon']) }}" alt="" class="sqv5-trust__icon" />
          <div class="sqv5-trust__info">
            <span class="sqv5-trust__title">{{ $__feature['title'] }}</span>
            <span class="sqv5-trust__sub">{{ $__feature['sub'] }}</span>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>
