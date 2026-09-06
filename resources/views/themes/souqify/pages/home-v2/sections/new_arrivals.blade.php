@php
    $__newInCards = collect($newInProducts ?? [])->values()->map(function ($product) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

        return [
            'id' => $product->id,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'sold' => __('70% Sold'),
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 22),
            'weight' => $variant?->weight ? $variant->weight . 'g' : null,
            'subtitle' => $product->centralProduct?->category?->name ?? '',
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
            'delivery' => __('Delivered by 24 March'),
            'stock' => __('Only 5 left'),
        ];
    });
    // Figma stacks three Mobile Cards per column, 9.61px apart.
    $__newInColumns = $__newInCards->chunk(3);
@endphp

{{-- Figma: Frame 1984080239 (1440 x 465.37) - the green wave over #8B03BD,
     24px 56px padding, 24px gap: a 50px header then the 343.37px row of
     columns (14.41px apart), each column three stacked Mobile Cards.
     The row measures 1691 against the 1328 the padding leaves, so it runs as a
     carousel: three whole columns and a sliver of the fourth.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv2-newin {
        /* The wave over the purple ground. Set here rather than through the
           shared .stripe-bg-purple rule: that rule's url() is relative to
           public/souqify-1/styles.css, and v2 ships its CSS through Vite, so the
           path never resolves on the page. */
        background-color: var(--color-brand-purple, #8B03BD);
        background-image: url('{{ asset('souqify-1/assets/images/new-in.png') }}');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 24px 16px;
        gap: 16px;
    }
    /* Frame 1984080017 */
    .sqv2-newin__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    .sqv2-newin__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 26px;
        line-height: 1.25;              /* 50 / 40 */
        color: #FFFFFF;
        margin: 0;
    }
    .sqv2-newin__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;
        letter-spacing: 0.5px;
        color: #FFFFFF;
        white-space: nowrap;
        text-decoration: none;
    }

    /* Frame 1984080441: the column row. */
    .sqv2-newin__row {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    .sqv2-newin__row .swiper-wrapper { align-items: flex-start; }
    /* Frame 1984080244: one column of three cards, 9.61px apart. Widths are set
       in CSS, not left to Swiper: the v2 bundle may mount a wrapper with
       slidesPerView:"auto" first, and an auto slide with no width blows the
       column up to the full row. */
    .sqv2-newin__col {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 9.61px;
        height: auto;
        /* Phones carry one full-width column of three cards. */
        width: 100%;
    }
    .sqv2-newin__col > * { width: 100%; }
    .sqv2-newin__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: rgba(255, 255, 255, 0.8);
    }

    @media (min-width: 640px) and (max-width: 1023px) {
        .sqv2-newin__col { width: calc((100% - 1 * 16px) / 1.6); }
    }

    @media (min-width: 1024px) {
        .sqv2-newin {
            /* The comp's content column is 1328px inside the 1440 canvas. */
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv2-newin__title { font-size: clamp(32px, 2.778vw, 40px); }
        .sqv2-newin__seeall { font-size: clamp(15px, 1.389vw, 20px); }
        /* Three whole columns plus a sliver of the fourth, 14.41px apart. */
        .sqv2-newin__col { width: calc((100% - 3 * 14.41px) / 3.35); }
    }
</style>

<!-- ============ NEW IN ============ -->
<section class="sqv2-newin">
  <div class="sqv2-newin__head">
    <h2 class="sqv2-newin__title">{{ __('New In') }}</h2>
    <a href="{{ route('tenant.storefront.new-in') }}" class="sqv2-newin__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__newInCards->isNotEmpty())
    <div class="swiper newin-swiper sqv2-newin__row">
      <div class="swiper-wrapper" id="newInWrapper">
        @foreach ($__newInColumns as $__index => $__column)
          <div class="swiper-slide sqv2-newin__col" wire:key="new-in-v2-col-{{ $__index }}">
            @foreach ($__column as $p)
              <div wire:key="new-in-v2-{{ $p['id'] }}">
                @include('themes.souqify.pages.home-v2.sections.partials.purple_mobile_card', ['p' => $p])
              </div>
            @endforeach
          </div>
        @endforeach
      </div>
    </div>
  @else
    <p class="sqv2-newin__empty">{{ __('No new arrivals yet.') }}</p>
  @endif
</section>

{{-- Mounted here rather than in resources/js/souqify-v2-carousels.js: that file
     ships through Vite, so a change there needs a rebuild before it reaches the
     page. This runs against the Swiper bundle the v2 scripts partial loads. --}}
<script>
(function () {
  function mountNewInV2() {
    var el = document.querySelector('.newin-swiper');
    if (!el || typeof Swiper === 'undefined') return;
    if (el.swiper) {
      if (el.swiper.__sqv2NewIn) return;
      el.swiper.destroy(true, true);
    }

    var sw = new Swiper(el, {
      slidesPerView: 1,
      spaceBetween: 12,
      breakpoints: {
        640: { slidesPerView: 1.6, spaceBetween: 16 },
        // Three whole columns and a sliver of the fourth, 14.41px apart.
        1024: { slidesPerView: 3.35, spaceBetween: 14.41 },
      },
    });
    sw.__sqv2NewIn = true;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountNewInV2);
  } else {
    mountNewInV2();
  }
  window.addEventListener('load', mountNewInV2);
})();
</script>
