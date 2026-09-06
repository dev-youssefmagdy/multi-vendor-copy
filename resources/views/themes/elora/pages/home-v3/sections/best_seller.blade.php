    @php
      $currency = $currentCurrency ?? null;
      $symbol = data_get($currency, 'symbol', '$');
      $rate = (float) data_get($currency, 'conversion_rate', 1.0);

      $bestSellerProducts = $bestSelling->map(function ($product) use ($symbol, $rate) {
          $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
          $pricing = $product->storefrontPricing($variant);
          $hasDiscount = (bool) $pricing['has_discount'];
          $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? asset('elora-3/assets/images/product-placeholder.svg');
          $rating = (float) ($product->average_rating ?? 0);
          $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

          return [
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
              'desc' => $product->centralProduct?->category?->name,
              'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : '',
              'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : '',
          ];
      });
    @endphp
{{-- Figma: Frame 1984080239 (375 x 702.81) - the pink panel at 18px 16px
     padding, a 343-wide column with a 12px gap: the 23px header, the card block
     (163.52 x 268.71 cards, right column dropped 28.68px), the bottom pink fade
     and the 121 x 38 Explore all pill. Four cards show at a time and a swipe
     brings the next four.
     These rules live here rather than in resources/css/elora-v3.css: that file
     ships through Vite and needs a rebuild to reach the page, while these come
     later in document order and win. --}}
<style>
    @media (max-width: 1023.98px) {
        .pattern-bestseller { padding: 18px 16px; gap: 12px; }
        /* Title centred (Figma margin: 0 auto); the dots leave the flow so they
           do not push it off-centre. The padding gives the row - and the dots
           sitting in it - air above and below, so the bullets do not touch the
           first card. */
        .elora-v3-best__head {
            position: relative;
            justify-content: center;
            padding: 8px 0 14px;
        }
        /* Frame 1984080197: the dots stand upright at the right edge of the
           header. Swiper stamps its own .swiper-pagination-horizontal rules on
           this element (left: 0; width: 100%; bullet sizing), so each one is
           overridden here rather than fought with ordering. */
        #bestSellerDotsV3 {
            position: absolute !important;
            left: auto !important;
            right: 0 !important;
            top: 50% !important;
            bottom: auto !important;
            width: auto !important;
            height: auto !important;
            transform: translateY(-50%);
            flex-direction: column;
            align-items: center;
            gap: 3px;
        }
        /* Undo the static CSS grid from elora-v3.css - Swiper's Grid module
           lays the rows out. flex-wrap: wrap is what Swiper's own .swiper-grid
           rule uses, so the 2 x 2 block stays right even before the mount. */
        #bestSellerWrapper {
            display: flex;
            flex-wrap: wrap;
            align-content: flex-start;
            column-gap: 0;
            row-gap: 0;
        }
        /* Widths come from Swiper's Grid module (inline, 163.52px against the
           343 column at a 15.96px gap) - a CSS width here would override it. */
        #bestSellerWrapper > .swiper-slide { box-sizing: border-box; }
        /* Until Swiper initialises the wrapper is a plain wrapping flex row, so
           the block still needs two columns and a four-card cap. */
        .bestseller-swiper:not(.swiper-initialized) #bestSellerWrapper > .swiper-slide {
            width: calc((100% - 15.96px) / 2);
        }
        /* Cards in the right column sit 28.68px lower (top 63.68 vs 35).
           The class is stamped from the slide's measured x below - DOM parity
           breaks on a last page that does not hold a full four. */
        /* With grid fill: 'row' the column is the slide's DOM parity, so the
           rule stands on its own; .is-lifted from the script below is the
           belt-and-braces pass for any page Swiper reorders. */
        #bestSellerWrapper > .swiper-slide:nth-child(even),
        #bestSellerWrapper > .swiper-slide.is-lifted {
            transform: translateY(28.68px);
        }
        /* A last page that does not fill both columns keeps its cards flat. */
        #bestSellerWrapper > .swiper-slide.is-flat {
            transform: none;
        }
        .bestseller-swiper { padding-top: 4px; padding-bottom: 30px; }
        /* Until Swiper initialises, the wrapper is a plain wrapping flex row,
           so every product would stack down the page. Keep only the first four
           visible in that state - the mount below then pages the rest. */
        .bestseller-swiper:not(.swiper-initialized) #bestSellerWrapper > .swiper-slide:nth-child(n + 5) {
            display: none;
        }
        /* Rectangle 5975: the pink wash over the bottom of the card block. */
        .elora-v3-best__fade {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 225px;
            z-index: 3;
            pointer-events: none;
            background: linear-gradient(180deg, rgba(254, 58, 106, 0) 0%, #FE3A6A 100%);
        }
        /* Frame 1984080196: 121 x 38, 1px white stroke, radius 34. */
        .elora-v3-best__cta {
            position: relative;
            z-index: 4;
            width: 121px;
            height: 38px;
            padding: 8px;
        }
        #bestSellerDotsV3 .swiper-pagination-bullet {
            width: 8px !important;
            height: 8px !important;
            margin: 0 !important;
            border-radius: 29px;
            background: #F0F0F0;
            opacity: 1 !important;
            cursor: pointer;
        }
        /* Active bullet is the comp's 8 x 24 pill, standing upright. */
        #bestSellerDotsV3 .swiper-pagination-bullet-active {
            height: 24px !important;
            background: #FFDB4C;
        }
    }
    @media (min-width: 1024px) {
        .elora-v3-best__fade { display: none; }
    }
</style>

{{-- Mounted here rather than in resources/js/elora-v3-carousels.js: that file
     ships through Vite, so a change there needs a rebuild before it reaches the
     page. Runs against the Swiper 11 bundle the v3 scripts partial loads. --}}
<script>
(function () {
  var MOBILE = '(max-width: 1023.98px)';

  function mountBestSellerV3() {
    var el = document.getElementById('bestSellerSwiper');
    if (!el || typeof Swiper === 'undefined') return;

    var mq = window.matchMedia(MOBILE);

    var sync = function () {
      var isMobile = mq.matches;
      var current = el.swiper;

      // The Vite bundle mounts (or destroys) this element on its own, so an
      // instance that is not ours is torn down before we take over.
      if (current && current.__eloraV3BestGrid !== isMobile) {
        current.destroy(true, true);
        current = null;
      }
      if (current) return;

      if (!isMobile) {
        // Desktop keeps the comp's auto-width row.
        var wide = new Swiper(el, { slidesPerView: 'auto', spaceBetween: 32 });
        wide.__eloraV3BestGrid = false;
        return;
      }

      // Two columns x two rows = four cards per page; a swipe brings the next
      // four. grid is init-time only, hence the create/destroy on breakpoint.
      var grid = new Swiper(el, {
        slidesPerView: 2,
        slidesPerGroup: 2,
        grid: { rows: 2, fill: 'row' },
        spaceBetween: 15.96,
        pagination: { el: '#bestSellerDotsV3', clickable: true },
      });
      grid.__eloraV3BestGrid = true;
      tagColumns(grid);
      grid.on('resize', function () { tagColumns(grid); });
      grid.on('slidesGridLengthChange', function () { tagColumns(grid); });
    };

    // The stagger follows the visual column, not the DOM index: Swiper reorders
    // grid slides, and a final page with fewer than four cards flips the
    // odd/even mapping.
    function tagColumns(instance) {
      var slides = instance.slides;
      if (!slides || !slides.length) return;
      var lefts = [];
      for (var i = 0; i < slides.length; i++) {
        lefts.push(slides[i].getBoundingClientRect().left);
      }
      var min = Math.min.apply(null, lefts);
      for (var j = 0; j < slides.length; j++) {
        // Slides sit in one of two columns per page; anything more than a third
        // of a slide right of its page's left edge is the right-hand column.
        var offset = (lefts[j] - min) % (instance.width || 1);
        var isRight = offset > (slides[j].offsetWidth || 1) * 0.5;
        slides[j].classList.toggle('is-lifted', isRight);
        slides[j].classList.toggle('is-flat', !isRight);
      }
    }

    sync();
    mq.addEventListener('change', sync);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountBestSellerV3);
  } else {
    mountBestSellerV3();
  }
  window.addEventListener('load', mountBestSellerV3);
})();
</script>
    <section
      class="pattern-bestseller relative mt-[16px] lg:mt-0 px-[16px] lg:px-[56px] py-[16px] lg:py-[32px] flex flex-col items-center gap-[12px] lg:gap-[24px]"
      wire:ignore
    >
      <div class="elora-v3-best__head w-full flex items-center justify-between lg:justify-center gap-[8px]">
        <h2 class="font-medium text-[18px] leading-[23px] lg:text-[32px] text-white">
          {{ __('Best Seller') }}
        </h2>
        {{-- Swiper fills this with one bullet per page of four cards. --}}
        <div class="flex lg:hidden items-end gap-[3px]" id="bestSellerDotsV3"></div>
      </div>
      @if ($bestSellerProducts->isEmpty())
        <p class="text-sm text-white/70 py-6 w-full">{{ __('No best sellers yet.') }}</p>
      @else
        <div class="swiper bestseller-swiper w-full" id="bestSellerSwiper">
          <div class="swiper-wrapper" id="bestSellerWrapper">
            @foreach ($bestSellerProducts as $p)
              @include('themes.elora.pages.home-v3.sections.partials.best_seller_card', ['p' => $p])
            @endforeach
          </div>
        </div>
      @endif
      <span class="elora-v3-best__fade lg:hidden" aria-hidden="true"></span>
      <a
        href="{{ route('tenant.storefront.best-selling') }}"
        class="elora-v3-best__cta border border-white rounded-full h-[44px] lg:h-[64px] px-[24px] lg:px-[32px] flex items-center justify-center cursor-pointer"
      >
        <span
          class="font-medium text-white text-[14px] lg:text-[20px] tracking-[0.5px]"
          >{{ __('Explore all') }}</span
        >
      </a>
    </section>
