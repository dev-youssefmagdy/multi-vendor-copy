    @if ($flashSales->isNotEmpty())
    @php
      $firstSale = $flashSales->first();
      $currency = $currentCurrency ?? null;
      $symbol = data_get($currency, 'symbol', '$');
      $rate = (float) data_get($currency, 'conversion_rate', 1.0);

      $flashSaleProducts = $flashProducts->map(function ($product) use ($symbol, $rate) {
          $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
          $pricing = $product->storefrontPricing($variant);
          $hasDiscount = (bool) $pricing['has_discount'];
          $img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? asset('elora-3/assets/images/product-placeholder.svg');

          return [
              'url' => route('tenant.storefront.product', $product->slug),
              'image' => $img,
              'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
              'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
              'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : '',
              'discount' => $hasDiscount ? '-' . (int) round((float) $pricing['discount_percentage']) . '%' : '',
          ];
      });

      $countdownRemaining = $firstSale->end_date ? max(0, now()->diffInSeconds($firstSale->end_date, false)) : 0;
      $countdownH = str_pad((string) intdiv($countdownRemaining, 3600), 2, '0', STR_PAD_LEFT);
      $countdownM = str_pad((string) intdiv($countdownRemaining % 3600, 60), 2, '0', STR_PAD_LEFT);
      $countdownS = str_pad((string) ($countdownRemaining % 60), 2, '0', STR_PAD_LEFT);
    @endphp

{{-- Phone layout for the strip: a 2 x 2 block of cards that pages four at a
     time. These rules live here rather than in resources/css/elora-v3.css
     because that file ships through Vite and needs a rebuild to reach the page;
     coming later in document order, they win. --}}
<style>
    @media (max-width: 1023.98px) {
        /* Undo the static CSS grid from elora-v3.css - Swiper's Grid module
           lays the rows out. flex-wrap: wrap is what Swiper's own .swiper-grid
           rule uses, so the block stays correct even before the mount runs. */
        #flashSaleWrapper {
            display: flex;
            flex-wrap: wrap;
            align-content: flex-start;
            gap: 0;
        }
        /* Every product stays in the carousel; the static layout hid all but
           the first four. */
        #flashSaleWrapper .swiper-slide:nth-child(n + 5) { display: block; }
        /* Two cards per row, a hairline 4px apart. */
        #flashSaleWrapper > .swiper-slide {
            width: calc((100% - 4px) / 2);
            height: auto;
        }
        /* Cards stand apart now, so each one carries its own corners. */
        #flashSaleWrapper > .swiper-slide > a {
            border-radius: 8px;
            overflow: hidden;
        }
        .flashsale-swiper { padding-bottom: 4px; }
        /* The comp keeps the next arrow beside the countdown on phones. */
        .elora-v3-flash__next { display: block; }
        .elora-v3-flash__next img { height: 26px; }
        /* No Explore all pill on the phone comp. */
        .elora-v3-flash__cta { display: none; }
    }
</style>

{{-- Mounted here rather than in resources/js/elora-v3-carousels.js: that file
     ships through Vite, so a change there needs a rebuild before it reaches the
     page. Runs against the Swiper 11 bundle the v3 scripts partial loads. --}}
<script>
(function () {
  var MOBILE = '(max-width: 1023.98px)';

  function mountFlashV3() {
    var el = document.getElementById('flashSaleSwiper');
    if (!el || typeof Swiper === 'undefined') return;

    var mq = window.matchMedia(MOBILE);

    var sync = function () {
      var isMobile = mq.matches;
      var current = el.swiper;

      // The Vite bundle mounts (or destroys) this element on its own, so an
      // instance that is not ours is torn down before we take over.
      if (current && current.__eloraV3FlashGrid !== isMobile) {
        current.destroy(true, true);
        current = null;
      }
      if (current) return;

      if (!isMobile) {
        var wide = new Swiper(el, {
          slidesPerView: 4.5,
          spaceBetween: 0,
          navigation: { nextEl: '#flashSaleNext' },
        });
        wide.__eloraV3FlashGrid = false;
        return;
      }

      // Two columns x two rows = four cards per page; a swipe brings the next
      // four. grid is init-time only, hence the create/destroy on breakpoint.
      var grid = new Swiper(el, {
        slidesPerView: 2,
        slidesPerGroup: 2,
        grid: { rows: 2, fill: 'row' },
        spaceBetween: 4,
        navigation: { nextEl: '#flashSaleNext' },
      });
      grid.__eloraV3FlashGrid = true;
    };

    sync();
    mq.addEventListener('change', sync);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountFlashV3);
  } else {
    mountFlashV3();
  }
  window.addEventListener('load', mountFlashV3);
})();
</script>
    <section
      class="pattern-flash-sale rounded-t-[16px] mt-[16px] lg:mt-0 px-[16px] lg:px-[56px] py-[16px] lg:py-[32px] flex flex-col gap-[16px] lg:gap-[24px]"
      wire:ignore
    >
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-[8px] lg:gap-[8px]">
          <img
            src="{{ asset('elora-3/assets/icons/icon-flash.svg') }}"
            alt=""
            class="size-[20px] lg:size-[40px]"
          />
          <h2 class="font-semibold text-[20px] lg:text-[32px] text-white">
            {{ __('Flash Sale') }}
          </h2>
        </div>
        <div class="flex items-center gap-[8px] lg:gap-[28px]" @if($firstSale->end_date) data-countdown="{{ $firstSale->end_date->timestamp }}" @endif>
          <div class="flex items-center gap-[3px] lg:gap-[5px]">
            <span
              class="font-semibold text-[13px] lg:text-[20px] tracking-[0.5px] lg:tracking-[0.83px]"
              style="color: var(--color-accent-yellow)"
              >{{ __('Ends in') }}</span
            >
            <span
              id="flashHours"
              class="flex items-center justify-center font-semibold text-[16px] lg:text-[40px] tracking-[0.83px] text-white rounded-[8px] lg:rounded-[12px] h-[36px] lg:h-[73px] w-[36px] lg:w-[77px]"
              style="
                background: var(--color-brand-pink);
                border: 1.5px solid white;
              "
              >{{ $countdownH }}</span
            >
            <span
              id="flashMinutes"
              class="flex items-center justify-center font-semibold text-[16px] lg:text-[40px] tracking-[0.83px] text-white rounded-[8px] lg:rounded-[12px] h-[36px] lg:h-[73px] w-[36px] lg:w-[77px]"
              style="
                background: var(--color-brand-pink);
                border: 1.5px solid white;
              "
              >{{ $countdownM }}</span
            >
            <span
              id="flashSeconds"
              class="flex items-center justify-center font-semibold text-[16px] lg:text-[40px] tracking-[0.83px] text-white rounded-[8px] lg:rounded-[12px] h-[36px] lg:h-[73px] w-[36px] lg:w-[77px]"
              style="
                background: var(--color-brand-pink);
                border: 1.5px solid white;
              "
              >{{ $countdownS }}</span
            >
          </div>
          <button
            id="flashSaleNext"
            type="button"
            aria-label="Next"
            class="elora-v3-flash__next hidden lg:block cursor-pointer"
          >
            <img
              src="{{ asset('elora-3/assets/icons/arrow-outlined.svg') }}"
              alt=""
              class="h-[57px] w-auto"
            />
          </button>
        </div>
      </div>
      <div class="swiper card-swiper flashsale-swiper" id="flashSaleSwiper">
        <div class="swiper-wrapper" id="flashSaleWrapper">
          @forelse ($flashSaleProducts as $p)
            @include('themes.elora.pages.home-v3.sections.partials.flash_card', ['p' => $p])
          @empty
            <p class="text-sm text-white/70 py-4">{{ __('No flash sale products at the moment.') }}</p>
          @endforelse
        </div>
      </div>
      <div class="elora-v3-flash__cta flex items-center justify-center">
        <a
          href="{{ route('tenant.storefront.best-selling') }}"
          class="border border-white rounded-full h-[44px] lg:h-[64px] px-[24px] lg:px-[55px] flex items-center justify-center cursor-pointer"
        >
          <span
            class="font-medium text-white text-[14px] lg:text-[20px] tracking-[0.5px]"
            >{{ __('Explore all') }}</span
          >
        </a>
      </div>
    </section>
    @endif
