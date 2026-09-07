@php
    $__trendingCards = collect($trendingNowProducts ?? [])->map(function ($product) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

        return [
            'id' => $product->id,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'desc' => \Illuminate\Support\Str::limit($product->translationValue('short_description') ?? '', 30),
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
            // Design-only fields with no backing data yet; same placeholder convention
            // the elora v3/v4 cards already use.
            'weight' => null,
            'sold' => '70% ' . __('Sold'),
            'progress' => 82.8,          // 190.79 / 230.41 in the comp
            'ordered' => __('5 ordered last 30 min'),
            'delivery' => __('Delivered by 24 March'),
            'stock' => __('Only 5 left'),
        ];
    });
@endphp

{{-- Figma: Frame 1984080216 (1440x327.46) - 24px 56px padding, 24px gap,
     50px header + 205.46px card row. Card = "Mobile Card" 463.76x205.46.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv4-trend {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 24px 16px;
        gap: 16px;
        /* The comp's panel is the page grey, not white - the cards read white
           against it. */
        background: var(--color-page-bg, #F4F4F4);
    }
    .sqv4-trend__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 0;
        gap: 8px;
        width: 100%;
    }
    .sqv4-trend__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 24px;
        line-height: 1.25;              /* 50 / 40 */
        color: #000000;
        margin: 0;
    }
    .sqv4-trend__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #5A9B00;
        white-space: nowrap;
    }
    .sqv4-trend__row {
        width: 100%;
        overflow: hidden;
    }
    .sqv4-trend__row .swiper-wrapper {
        align-items: stretch;
    }
    .sqv4-trend__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: var(--color-footer-text-muted);
    }

    /* ---------- Card ---------- */
    .sqv4-trend-card {
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        padding: 0;
        width: 100%;
        background: #FDFDFD;
        border-radius: 8.80556px;
        overflow: hidden;
    }
    .sqv4-trend-card__media {
        position: relative;
        display: block;
        /* 193.72 / 463.76 */
        width: 41.77%;
        flex: none;
        align-self: stretch;
        background: var(--color-card-image-bg);
        border-radius: 7.37989px 7.37989px 0px 0px;
        overflow: hidden;
    }
    .sqv4-trend-card__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .sqv4-trend-card__sale {
        position: absolute;
        top: 0;
        right: 0;
        z-index: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 4.85189px;
        background: #FFD428;
        border-radius: 0px 6.46919px;
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 9px;
        line-height: 1.2366;            /* 14 / 11.3211 */
        letter-spacing: 0.404324px;
        color: #242424;
        white-space: nowrap;
    }
    .sqv4-trend-card__heart {
        position: absolute;
        top: 4.61243px;
        left: 5.53492px;
        z-index: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 5px;
        width: 24px;
        height: 24px;
        background: #FFFFFF;
        box-shadow: 0px 3.68995px 3.68995px rgba(0, 0, 0, 0.15);
        border-radius: 46.1243px;
        border: 0;
        cursor: pointer;
    }
    .sqv4-trend-card__heart img { width: 100%; height: 100%; object-fit: contain; }
    .sqv4-trend-card__cart {
        position: absolute;
        bottom: 4.61243px;
        right: 5.53492px;
        z-index: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 3.68995px 8px;
        width: 40px;
        height: 32px;
        background: #FDFDFD;
        border-radius: 14.7598px;
        border: 0;
        cursor: pointer;
    }
    .sqv4-trend-card__cart img { width: 100%; height: 100%; object-fit: contain; }

    .sqv4-trend-card__info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        padding: 5.87037px 8px;
        gap: 8px;
        flex: 1;
        min-width: 0;
    }
    .sqv4-trend-card__head,
    .sqv4-trend-card__meta,
    .sqv4-trend-card__status {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 3.69px;
        width: 100%;
        min-width: 0;
    }
    .sqv4-trend-card__titlerow {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 1.84px;
        width: 100%;
        min-width: 0;
    }
    .sqv4-trend-card__name {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 13px;
        line-height: 1.2654;            /* 26 / 20.5463 */
        letter-spacing: 0.461243px;
        color: #121212;
        text-decoration: none;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sqv4-trend-card__weight {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 13px;
        line-height: 1.0448;            /* 23 / 22.0139 */
        letter-spacing: 0.461243px;
        color: #5A9B00;
        flex: none;
    }
    .sqv4-trend-card__desc {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 11px;
        line-height: 1.2492;            /* 22 / 17.6111 */
        letter-spacing: 0.461243px;
        color: #ADADAD;
        margin: 0;
        width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sqv4-trend-card__progress {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 1.47px;
        width: 100%;
    }
    .sqv4-trend-card__track {
        position: relative;
        width: 100%;
        height: 5px;
        background: #D9D9D9;
        border-radius: 35.2222px;
        overflow: hidden;
    }
    .sqv4-trend-card__fill {
        display: block;
        height: 100%;
        background: #5A9B00;
        border-radius: 35.2222px;
    }
    .sqv4-trend-card__ordered {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 10px;
        line-height: 1.2265;            /* 18 / 14.6759 */
        letter-spacing: 0.461243px;
        color: #5A9B00;
        margin: 0;
        white-space: nowrap;
    }

    .sqv4-trend-card__rating {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 7.38px;
    }
    .sqv4-trend-card__stars {
        /* 63.18 x 9.22 in the comp */
        width: 46px;
        height: 6.72px;
        flex: none;
    }
    .sqv4-trend-card__ratingtext {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 11px;
        line-height: 1.2492;
        letter-spacing: 0.461243px;
        color: #ADADAD;
        white-space: nowrap;
    }
    .sqv4-trend-card__prices {
        display: flex;
        flex-direction: row;
        align-items: flex-end;
        gap: 7.38px;
        flex-wrap: wrap;
    }
    .sqv4-trend-card__price {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 15px;
        line-height: 1.2776;            /* 30 / 23.4815 */
        color: #121212;
        white-space: nowrap;
    }
    .sqv4-trend-card__oldprice {
        font-family: 'Outfit', sans-serif;
        font-weight: 300;
        font-size: 9px;
        line-height: 1.2389;            /* 16 / 12.9148 */
        text-decoration-line: line-through;
        color: #ADADAD;
        white-space: nowrap;
    }
    .sqv4-trend-card__discount {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 11px;
        line-height: 1.2492;
        letter-spacing: 0.461243px;
        color: #FF522C;
        white-space: nowrap;
    }

    .sqv4-trend-card__statusrow {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 5.87px;
        min-width: 0;
    }
    .sqv4-trend-card__statusrow--stock { gap: 7.38px; }
    .sqv4-trend-card__truck { width: 13px; height: 13px; flex: none; }
    .sqv4-trend-card__cartx { width: 11px; height: 11px; flex: none; }
    .sqv4-trend-card__statustext {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 9px;
        line-height: 1.2775;            /* 15 / 11.7407 */
        color: #2AAF2F;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sqv4-trend-card__statustext--sm {
        font-size: 8.5px;
        line-height: 1.2647;            /* 14 / 11.0698 */
    }

    @media (min-width: 1024px) {
        .sqv4-trend {
            /* 24px 56px padding, 24px gap */
            padding: 24px clamp(24px, 3.889vw, 56px);
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv4-trend__title {
            /* 40px / 50px line-height, weight 800 */
            font-size: clamp(28px, 2.778vw, 40px);
        }
        .sqv4-trend__seeall {
            font-size: clamp(15px, 1.389vw, 20px);
        }
        .sqv4-trend-card {
            /* 205.46 tall; width comes from Swiper's slidesPerView (1328 / 463.76
               = 2.86 cards across the padded row, matching the comp). */
            height: clamp(146px, 14.268vw, 205.46px);
        }
        .sqv4-trend-card__sale {
            /* 11.3211px */
            font-size: clamp(9px, 0.7862vw, 11.3211px);
        }
        .sqv4-trend-card__heart {
            /* 29.52 box */
            width: clamp(24px, 2.05vw, 29.52px);
            height: clamp(24px, 2.05vw, 29.52px);
        }
        .sqv4-trend-card__cart {
            /* 52.58 x 41.51, 11.0698px side padding */
            width: clamp(40px, 3.6514vw, 52.58px);
            height: clamp(32px, 2.8826vw, 41.51px);
            padding: 3.68995px clamp(8px, 0.7687vw, 11.0698px);
        }
        .sqv4-trend-card__info {
            /* 5.87037px 11.7407px padding, 11.74px gap */
            padding: 5.87037px clamp(8px, 0.8153vw, 11.7407px);
            gap: clamp(8px, 0.8153vw, 11.74px);
        }
        .sqv4-trend-card__name   { font-size: clamp(13px, 1.4268vw, 20.5463px); }
        .sqv4-trend-card__weight { font-size: clamp(13px, 1.5287vw, 22.0139px); }
        .sqv4-trend-card__desc,
        .sqv4-trend-card__ratingtext,
        .sqv4-trend-card__discount { font-size: clamp(11px, 1.2230vw, 17.6111px); }
        .sqv4-trend-card__track {
            /* 7.34px tall */
            height: clamp(5px, 0.5097vw, 7.34px);
        }
        .sqv4-trend-card__ordered { font-size: clamp(10px, 1.0192vw, 14.6759px); }
        .sqv4-trend-card__stars {
            /* 63.18 x 9.22 */
            width: clamp(46px, 4.3875vw, 63.18px);
            height: clamp(6.72px, 0.6403vw, 9.22px);
        }
        .sqv4-trend-card__price    { font-size: clamp(15px, 1.6306vw, 23.4815px); }
        .sqv4-trend-card__oldprice { font-size: clamp(9px, 0.8969vw, 12.9148px); }
        .sqv4-trend-card__truck {
            width: clamp(13px, 1.2230vw, 17.61px);
            height: clamp(13px, 1.2230vw, 17.61px);
        }
        .sqv4-trend-card__cartx {
            width: clamp(11px, 1.0250vw, 14.76px);
            height: clamp(11px, 1.0250vw, 14.76px);
        }
        .sqv4-trend-card__statustext     { font-size: clamp(9px, 0.8153vw, 11.7407px); }
        .sqv4-trend-card__statustext--sm { font-size: clamp(8.5px, 0.7687vw, 11.0698px); }
    }
</style>

<!-- ============ TRENDING NOW ============ -->
<section class="sqv4-trend">
  <div class="sqv4-trend__head">
    <h2 class="sqv4-trend__title">{{ __('Trending Now') }}</h2>
    <a href="{{ route('tenant.storefront.best-selling') }}" class="sqv4-trend__seeall">{{ __('see all') }}</a>
  </div>
  @if ($__trendingCards->isNotEmpty())
    <div class="swiper trending-swiper sqv4-trend__row">
      <div id="trendingWrapper" class="swiper-wrapper">
        @foreach ($__trendingCards as $p)
          @include('themes.souqify.pages.home-v4.sections.partials.trending_card', ['p' => $p])
        @endforeach
      </div>
    </div>
  @else
    <p class="sqv4-trend__empty">{{ __('No trending products yet.') }}</p>
  @endif
</section>
