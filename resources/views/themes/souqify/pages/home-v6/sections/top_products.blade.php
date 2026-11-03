@php
    $__bestSellerCards = collect($bestSelling ?? [])->values()->map(function ($product, $index) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();
        // Figma alternates the discount chip between orange and red across the row.
        $chip = [
            ['bg' => '#FF570F', 'color' => '#FDFDFD'],
            ['bg' => '#DE1709', 'color' => '#FDFDFD'],
        ][$index % 2];

        $__url = route('tenant.storefront.product', $product->slug);
        $__img = $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null;
        $__name = \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30);
        $__fav = json_encode([
            'slug' => $product->slug,
            'name' => $__name,
            'price' => round((float) $pricing['current_price'] * $rate, 2),
            'old_price' => $hasDiscount && $pricing['original_price'] !== null ? number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% Off' : null,
            'rating' => $rating,
            'image' => $__img,
            'url' => $__url,
            'added' => time(),
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'url' => $__url,
            'image' => $__img,
            'tag' => $product->cardBadgeText('🔥 '),
            'labels' => $product->cardLabels(),
            'name' => $__name,
            'weight' => null,
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
            'discountBg' => $chip['bg'],
            'discountColor' => $chip['color'],
            'outOfStock' => $product->stockStatus() === 'out_of_stock',
            'fav' => $__fav,
        ];
    });
@endphp

{{-- Figma: Frame 1984080383 (1440 x 584) - a full-bleed #FF1A90 panel whose top
     edge is the Union of fifteen 105.27px arches, 24px 56px padding, 24px gap:
     a 50px header then the 462px row of five 236.47px Deal Cards, 21px apart.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv6-best {
        position: relative;
        /* Breathing room above: the backdrop reaches above the panel's own box
           to draw the arches. */
        margin: 48px 0 0;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 12px 16px;
        gap: 16px;
        background: transparent;
    }
    .sqv6-best > *:not(.sqv6-best__bg):not(.sqv6-best__smoke) { position: relative; z-index: 1; }

    /* The whole pink surface: a deep hot-pink ground with blurred blobs floating
       over it. It starts above the panel and is masked into the arches up there,
       so the same cloud texture runs unbroken across the top edge. */
    .sqv6-best__bg {
        position: absolute;
        left: 0;
        right: 0;
        top: -26px;
        bottom: 0;
        z-index: 0;
        pointer-events: none;
        overflow: hidden;
        background:
            radial-gradient(ellipse 45.4% 52.3% at 81.8% 73.4%, rgba(196,0,92,0.41) 0%, rgba(196,0,92,0.23) 45%, rgba(196,0,92,0) 100%),
            radial-gradient(ellipse 28.2% 84.5% at 82.1% 49.7%, rgba(161,0,66,0.31) 0%, rgba(161,0,66,0.17) 45%, rgba(161,0,66,0) 100%),
            radial-gradient(ellipse 40.2% 71.3% at 52.2% 103.2%, rgba(176,0,80,0.30) 0%, rgba(176,0,80,0.17) 45%, rgba(176,0,80,0) 100%),
            radial-gradient(ellipse 41.4% 61.5% at 40.9% 80.3%, rgba(143,0,60,0.31) 0%, rgba(143,0,60,0.17) 45%, rgba(143,0,60,0) 100%),
            radial-gradient(ellipse 28.7% 56.3% at 91.7% 39.9%, rgba(196,0,92,0.38) 0%, rgba(196,0,92,0.21) 45%, rgba(196,0,92,0) 100%),
            radial-gradient(ellipse 26.4% 59.2% at 47.5% 15.4%, rgba(161,0,66,0.40) 0%, rgba(161,0,66,0.22) 45%, rgba(161,0,66,0) 100%),
            radial-gradient(ellipse 32.2% 62.1% at 104.0% 21.4%, rgba(176,0,80,0.33) 0%, rgba(176,0,80,0.18) 45%, rgba(176,0,80,0) 100%),
            radial-gradient(ellipse 33.9% 58.1% at 29.4% 92.5%, rgba(143,0,60,0.43) 0%, rgba(143,0,60,0.24) 45%, rgba(143,0,60,0) 100%),
            radial-gradient(ellipse 36.8% 56.9% at 84.0% 96.3%, rgba(255,61,159,0.30) 0%, rgba(255,61,159,0.17) 45%, rgba(255,61,159,0) 100%),
            radial-gradient(ellipse 32.2% 48.3% at 53.2% 85.9%, rgba(255,87,171,0.35) 0%, rgba(255,87,171,0.19) 45%, rgba(255,87,171,0) 100%),
            radial-gradient(ellipse 35.1% 48.3% at 94.8% 52.7%, rgba(255,46,155,0.35) 0%, rgba(255,46,155,0.19) 45%, rgba(255,46,155,0) 100%),
            radial-gradient(ellipse 34.5% 52.3% at 94.4% 46.2%, rgba(255,61,159,0.37) 0%, rgba(255,61,159,0.20) 45%, rgba(255,61,159,0) 100%),
            radial-gradient(ellipse 23.6% 73.0% at 73.9% 23.9%, rgba(255,87,171,0.35) 0%, rgba(255,87,171,0.19) 45%, rgba(255,87,171,0) 100%),
            var(--color-brand-pink, #FF1A90);
        /* Two mask layers, unioned: the repeated dome over the top 26px, and a
           solid block for everything below it. round stretches the dome to a
           whole number of columns so none is cut at the right edge. */
        -webkit-mask-image:
            radial-gradient(28px 26px at 50% 100%, #000 99%, transparent 100%),
            linear-gradient(#000, #000);
        mask-image:
            radial-gradient(28px 26px at 50% 100%, #000 99%, transparent 100%),
            linear-gradient(#000, #000);
        /* The block layer runs 1px into the dome band: butting them exactly at
           26px leaves a sub-pixel seam that renders as a hairline. */
        -webkit-mask-size: 56px 26px, 100% calc(100% - 25px);
        mask-size: 56px 26px, 100% calc(100% - 25px);
        -webkit-mask-position: 0 0, 0 100%;
        mask-position: 0 0, 0 100%;
        -webkit-mask-repeat: round, no-repeat;
        mask-repeat: round, no-repeat;
    }
    /* The pink clouds are painted as static radial gradients on .sqv6-best__bg. They used to be 13
       full-panel elements under filter: blur(60-90px) + will-change, which froze scrolling. */
    .sqv6-best__bg::after {
        content: '';
        position: absolute;
        inset: -6%;
        background-image: url('{{ asset('souqify-5/assets/images/best-smoke.svg') }}');
        background-size: 120% 120%;
        background-position: 30% 60%;
        background-repeat: no-repeat;
        transform: scaleX(-1);
        opacity: 0.8;
    }

    /* A second, fainter smoke layer drawn OVER the cards, so the trails read as
       depth in front of the product row rather than only behind it. It never
       takes pointer events, so the cards stay clickable. */
    .sqv6-best__smoke {
        position: absolute;
        inset: 0;
        z-index: 2;
        pointer-events: none;
        overflow: hidden;
    }
    /* Curved trails, not straight streaks: an SVG of soft S-curves stretched
       over the panel. */
    .sqv6-best__smoke {
        background-image: url('{{ asset('souqify-5/assets/images/best-smoke.svg') }}');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        opacity: 0.85;
    }

    /* Frame 1984080018 */
    .sqv6-best__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    .sqv6-best__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 24px;
        line-height: 1.25;              /* 50 / 40 */
        color: #FFFFFF;
        margin: 0;
    }
    .sqv6-best__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #FFFFFF;
        white-space: nowrap;
        text-decoration: none;
    }

    /* Frame 1984080381: the card row, clipped so the sixth card never peeks. */
    .sqv6-best__row {
        width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    /* Tops aligned so the stagger below is the only thing moving the cards. */
    .sqv6-best__row .swiper-wrapper { align-items: flex-start; }
    .sqv6-best__slide { height: auto; }
    /* Frames 1984080379/381/382/383/385 sit the cards at alternating heights -
       the odd ones high, the even ones dropped by the row's 23px gap. Keyed to
       nth-child, so every set of five a swipe brings in repeats the pattern. */
    .sqv6-best__slide:nth-child(2n) { padding-top: 14px; }
    /* Phone grid: an even 2x2 grid (no stagger), with its own bottom gap
       between rows. Overrides the desktop row's nth-child stagger above. */
    .sqv6-best--grid .sqv6-best__slide { padding-top: 0; margin-bottom: 12px; }
    /* Deal Card: 236.47 x 375.67, radius 7.15736, no shadow on the pink. */
    .sqv6-best__slide .sqv6-deal {
        border-radius: 7.15736px;
        box-shadow: none;
    }
    /* Square, uncropped photo: 289.73 / 472.89 of the card height equals the
       card width. */
    .sqv6-best__slide .sqv6-deal__img { object-fit: contain; }
    .sqv6-best__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: rgba(255, 255, 255, 0.8);
    }

    /* Phones: the panel is tall and narrow, so one stretched sheet bunches the
       ribbons in the middle. Three offset copies at different scales spread
       them across the top, middle and bottom, and left and right. */
    @media (max-width: 1023px) {
        .sqv6-best__smoke {
            background-image:
                url('{{ asset('souqify-5/assets/images/best-smoke.svg') }}'),
                url('{{ asset('souqify-5/assets/images/best-smoke.svg') }}'),
                url('{{ asset('souqify-5/assets/images/best-smoke.svg') }}');
            background-size: 170% 42%, 210% 38%, 150% 44%;
            background-position: -35% 2%, 55% 52%, -10% 100%;
            background-repeat: no-repeat, no-repeat, no-repeat;
            opacity: 0.8;
        }
        .sqv6-best__bg::after {
            background-image:
                url('{{ asset('souqify-5/assets/images/best-smoke.svg') }}'),
                url('{{ asset('souqify-5/assets/images/best-smoke.svg') }}');
            background-size: 230% 46%, 180% 40%;
            background-position: 70% 14%, -25% 78%;
            background-repeat: no-repeat, no-repeat;
        }
    }

    @media (min-width: 1024px) {
        .sqv6-best {
            /* The comp's content column is 1328px inside the 1440 canvas. */
            padding: 48px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv6-best { margin: clamp(64px, 5.556vw, 80px) 0 0; }
        .sqv6-best__bg {
            /* Wider, still shallow columns on desktop. */
            top: clamp(-34px, -2.361vw, -26px);
            -webkit-mask-image:
                radial-gradient(clamp(28px, 2.778vw, 40px) clamp(26px, 2.361vw, 34px) at 50% 100%, #000 99%, transparent 100%),
                linear-gradient(#000, #000);
            mask-image:
                radial-gradient(clamp(28px, 2.778vw, 40px) clamp(26px, 2.361vw, 34px) at 50% 100%, #000 99%, transparent 100%),
                linear-gradient(#000, #000);
            -webkit-mask-size: clamp(56px, 5.556vw, 80px) clamp(26px, 2.361vw, 34px), 100% calc(100% - clamp(26px, 2.361vw, 34px) + 1px);
            mask-size: clamp(56px, 5.556vw, 80px) clamp(26px, 2.361vw, 34px), 100% calc(100% - clamp(26px, 2.361vw, 34px) + 1px);
        }

        .sqv6-best__smoke { opacity: 0.7; }
        .sqv6-best__slide:nth-child(2n) { padding-top: clamp(14px, 1.597vw, 23px); }
        .sqv6-best__title { font-size: clamp(28px, 2.778vw, 40px); }
        .sqv6-best__seeall { font-size: clamp(15px, 1.389vw, 20px); }
    }
</style>

<!-- ============ BEST SELLER ============ -->
<section class="sqv6-best" wire:ignore>
  <div class="sqv6-best__bg" aria-hidden="true">
  </div>

  <div class="sqv6-best__head">
    <h2 class="sqv6-best__title">{{ __('Best Seller') }}</h2>
    <a href="{{ route('tenant.storefront.best-selling') }}" class="sqv6-best__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__bestSellerCards->isNotEmpty())
    <div class="swiper bestseller-swiper sqv6-best__row">
      <div class="swiper-wrapper" id="bestSellerWrapper">
        @foreach ($__bestSellerCards as $p)
          <div class="swiper-slide sqv6-best__slide" wire:key="best-seller-v6-{{ $p['id'] }}">
            @include('themes.souqify.pages.home-v6.sections.partials.pink_deal_card', ['p' => $p])
          </div>
        @endforeach
      </div>
    </div>
  @else
    <p class="sqv6-best__empty">{{ __('No best sellers yet.') }}</p>
  @endif
  <div class="sqv6-best__smoke" aria-hidden="true">
  </div>
</section>
