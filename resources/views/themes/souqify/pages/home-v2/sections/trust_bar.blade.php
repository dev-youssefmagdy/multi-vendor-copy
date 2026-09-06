{{-- Figma: "Featured" bar (1439 x 76.2 on the 1440 canvas), #FFD428.
     The four feature blocks measure more than the 1328px the 56px padding
     leaves, so the strip runs as a Swiper carousel at 3.5 slides per view
     instead of clipping. Sizing values are the Figma measurements as
     clamp(min, <value>/1440*100vw, max). --}}
<style>
    .sqv2-trust {
        background: var(--color-accent-yellow, #FFD428);
        box-shadow: 0px 13.0987px 65.4933px rgba(0, 38, 3, 0.08);
        --sqv2-trust-title: #000000;
        --sqv2-trust-sub: #636363;
    }
    .sqv2-trust__viewport {
        /* 13.0987px 56px padding on the 1440 canvas */
        padding: 8px 12px;
        overflow: hidden;
    }
    @media (prefers-reduced-motion: reduce) {
        .sqv2-trust .swiper-wrapper { transition-duration: 0ms !important; }
    }
    /* Feature: 39.3 icon + info, 26.2px apart, 50px tall. */
    .sqv2-trust__slide {
        display: flex;
        flex-direction: row;
        /* Phones show a feature and a half, so each slide reads left-aligned
           and the second one is cut by the viewport edge. */
        justify-content: flex-start;
        align-items: center;
        gap: 8px;
        height: 40px;
        min-width: 0;
    }
    .sqv2-trust__icon {
        width: 24px;
        height: 24px;
        flex: none;
    }
    .sqv2-trust__info {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        min-width: 0;
    }
    .sqv2-trust__info { overflow: hidden; }
    .sqv2-trust__title,
    .sqv2-trust__sub {
        font-family: 'Outfit', sans-serif;
        font-style: normal;
        /* 19.648px / 25px line-height / 0.818667px tracking */
        font-size: 11px;
        line-height: 1.2724;
        letter-spacing: 0.3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    .sqv2-trust__title { font-weight: 600; color: var(--sqv2-trust-title); font-size: 12px; }
    .sqv2-trust__sub   { font-weight: 400; color: var(--sqv2-trust-sub); }

    @media (min-width: 1024px) {
        /* Continuous marquee: linear easing turns Swiper's stepped autoplay into
           an even scroll. Desktop only - mobile is hand-swiped. */
        .sqv2-trust .swiper-wrapper { transition-timing-function: linear !important; }
        .sqv2-trust__viewport {
            padding: clamp(10px, 0.9096vw, 13.0987px) clamp(24px, 3.889vw, 56px);
        }
        .sqv2-trust__slide {
            /* Desktop packs several features per row, so they read left-aligned. */
            justify-content: flex-start;
            gap: clamp(14px, 1.8194vw, 26.2px);
        }
        .sqv2-trust__icon {
            width: clamp(28px, 2.7292vw, 39.3px);
            height: clamp(28px, 2.7292vw, 39.3px);
        }
        .sqv2-trust__info { height: 50px; overflow: visible; }
        .sqv2-trust__title,
        .sqv2-trust__sub {
            font-size: clamp(14px, 1.3644vw, 19.648px);
            letter-spacing: clamp(0.55px, 0.0569vw, 0.818667px);
            overflow: visible;
        }
    }
</style>

@php
    // Swiper's loop needs at least 2x slidesPerView to run without gaps, so the
    // four Figma features are repeated to fill the marquee. aria-hidden on the
    // repeats keeps screen readers from announcing each feature twice.
    $__trustFeatures = [
        ['icon' => 'icon-feature-truck.svg',      'title' => __('Free Shipping'),         'sub' => __('Free shipping on all your order')],
        ['icon' => 'icon-feature-headphones.svg', 'title' => __('Customer Support 24/7'), 'sub' => __('Instant access to Support')],
        ['icon' => 'icon-feature-bag.svg',        'title' => __('100% Secure Payment'),   'sub' => __('We ensure your money is save')],
        ['icon' => 'icon-feature-package.svg',    'title' => __('Money-Back Guarantee'),  'sub' => __('30 Days Money-Back Guarantee')],
    ];
    $__trustSlides = array_merge($__trustFeatures, $__trustFeatures);
@endphp

<!-- ============ FEATURED STRIP ============ -->
<section class="sqv2-trust">
  <div class="swiper trust-swiper sqv2-trust__viewport">
    <div class="swiper-wrapper">
      @foreach ($__trustSlides as $__index => $__feature)
        <div class="swiper-slide sqv2-trust__slide" @if ($__index >= count($__trustFeatures)) aria-hidden="true" @endif>
          <img src="{{ asset('souqify-1/assets/icons/' . $__feature['icon']) }}" alt="" class="sqv2-trust__icon" />
          <div class="sqv2-trust__info">
            <span class="sqv2-trust__title">{{ $__feature['title'] }}</span>
            <span class="sqv2-trust__sub">{{ $__feature['sub'] }}</span>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- Mounted here rather than in resources/js/souqify-v2-carousels.js: that file
     ships through Vite, so a change there needs a rebuild before it reaches the
     page. This runs against the Swiper bundle the v2 scripts partial loads. --}}
<script>
(function () {
  function mountTrustBarV2() {
    var el = document.querySelector('.trust-swiper');
    if (!el || el.swiper || typeof Swiper === 'undefined') return;

    // Desktop-only marquee: phones swipe by hand, so autoplay stays off there.
    // Read once at init - Swiper cannot toggle autoplay from a breakpoint.
    var marquee =
      window.matchMedia('(min-width: 1024px)').matches &&
      !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    new Swiper(el, {
      slidesPerView: 1.5,
      spaceBetween: 12,
      loop: true,
      autoplay: marquee
        ? { delay: 0, disableOnInteraction: false, pauseOnMouseEnter: true }
        : false,
      speed: marquee ? 4000 : 300,
      breakpoints: {
        640: { slidesPerView: 2.5, spaceBetween: 32 },
        // 52.39px is the Figma gap between feature blocks.
        1024: { slidesPerView: 3.5, spaceBetween: 52.39 },
      },
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountTrustBarV2);
  } else {
    mountTrustBarV2();
  }
  window.addEventListener('load', mountTrustBarV2);
})();
</script>
