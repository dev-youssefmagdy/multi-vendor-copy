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

        return [
            'id' => $product->id,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'tag' => '🔥 ' . __('Trending Now'),
            'stock' => __('Only 5 left - Hurry up'),
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'weight' => $variant?->weight ? $variant->weight . 'g' : null,
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
            'discountBg' => $chip['bg'],
            'discountColor' => $chip['color'],
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
        /* Breathing room above and below: the backdrop reaches above the panel's
           own box to draw the arches. */
        margin: 48px 0 32px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 24px 16px;
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
        background: var(--color-brand-pink, #FF1A90);
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
    /* Organic clouds: every blob is a soft ellipse under a heavy blur, so the
       edges dissolve into one another and read as smoke rather than as a set of
       circles. Three families - deep magenta shadow, mid pink, and cream/white
       glow - are interleaved in the DOM so light and dark stay intertwined.
       Positions are scattered, never mirrored left to right. */
    .sqv6-best__blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(60px);
        will-change: transform;
    }
    .sqv6-best__blob--s1 { left: 42.3%; top: 27.9%; width: 79%; height: 91%; background: #C4005C; opacity: 0.41; }
    .sqv6-best__blob--s2 { left: 57.6%; top: -23.8%; width: 49%; height: 147%; background: #A10042; opacity: 0.31; }
    .sqv6-best__blob--s3 { left: 17.2%; top: 41.2%; width: 70%; height: 124%; background: #B00050; opacity: 0.3; }
    .sqv6-best__blob--s4 { left: 4.9%; top: 26.8%; width: 72%; height: 107%; background: #8F003C; opacity: 0.31; }
    .sqv6-best__blob--s5 { left: 66.7%; top: -9.1%; width: 50%; height: 98%; background: #C4005C; opacity: 0.38; }
    .sqv6-best__blob--s6 { left: 24.5%; top: -36.1%; width: 46%; height: 103%; background: #A10042; opacity: 0.4; }
    .sqv6-best__blob--s7 { left: 76.0%; top: -32.6%; width: 56%; height: 108%; background: #B00050; opacity: 0.33; }
    .sqv6-best__blob--s8 { left: -0.1%; top: 42.0%; width: 59%; height: 101%; background: #8F003C; opacity: 0.43; }
    .sqv6-best__blob--p1 { left: 52.0%; top: 46.8%; width: 64%; height: 99%; background: #FF3D9F; opacity: 0.3; }
    .sqv6-best__blob--p2 { left: 25.2%; top: 43.9%; width: 56%; height: 84%; background: #FF57AB; opacity: 0.35; }
    .sqv6-best__blob--p3 { left: 64.3%; top: 10.7%; width: 61%; height: 84%; background: #FF2E9B; opacity: 0.35; }
    .sqv6-best__blob--p4 { left: 64.4%; top: 0.7%; width: 60%; height: 91%; background: #FF3D9F; opacity: 0.37; }
    .sqv6-best__blob--p5 { left: 53.4%; top: -39.6%; width: 41%; height: 127%; background: #FF57AB; opacity: 0.35; }

    /* Wisps: the only light in the field - very thin, tilted trails at low
       opacity, blurred just enough to stay soft. Sparse enough that the pink
       stays saturated instead of turning pale. */
    .sqv6-best__blob--w { display: none; }
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
    .sqv6-best__blob--w1 { left: 32.5%; top: -38.3%; width: 3%; height: 165%; background: rgba(255,255,255,0.30); opacity: 0.56; transform: rotate(-36deg); }
    .sqv6-best__blob--w2 { left: 79.0%; top: 13.3%; width: 3%; height: 113%; background: rgba(255,236,246,0.26); opacity: 0.9; transform: rotate(-26deg); }
    .sqv6-best__blob--w3 { left: 62.2%; top: 4.6%; width: 4%; height: 137%; background: rgba(255,246,236,0.24); opacity: 0.66; transform: rotate(26deg); }
    .sqv6-best__blob--w4 { left: 6.2%; top: -41.0%; width: 4%; height: 128%; background: rgba(255,255,255,0.30); opacity: 0.57; transform: rotate(-12deg); }
    .sqv6-best__blob--w5 { left: 37.2%; top: -37.3%; width: 3%; height: 86%; background: rgba(255,236,246,0.26); opacity: 0.9; transform: rotate(36deg); }
    .sqv6-best__blob--w6 { left: 72.0%; top: -55.4%; width: 4%; height: 133%; background: rgba(255,246,236,0.24); opacity: 0.73; transform: rotate(36deg); }
    .sqv6-best__blob--w7 { left: 43.1%; top: -6.9%; width: 4%; height: 86%; background: rgba(255,255,255,0.30); opacity: 0.6; transform: rotate(26deg); }
    .sqv6-best__blob--w8 { left: 81.4%; top: 16.7%; width: 2%; height: 102%; background: rgba(255,236,246,0.26); opacity: 0.59; transform: rotate(-26deg); }
    .sqv6-best__blob--w9 { left: 31.3%; top: -22.9%; width: 3%; height: 140%; background: rgba(255,246,236,0.24); opacity: 0.68; transform: rotate(-36deg); }
    .sqv6-best__blob--w10 { left: 79.4%; top: 15.4%; width: 5%; height: 126%; background: rgba(255,255,255,0.30); opacity: 0.76; transform: rotate(26deg); }
    .sqv6-best__blob--w11 { left: 8.1%; top: -25.6%; width: 4%; height: 151%; background: rgba(255,236,246,0.26); opacity: 0.81; transform: rotate(18deg); }
    .sqv6-best__blob--w12 { left: 34.1%; top: -53.4%; width: 3%; height: 140%; background: rgba(255,246,236,0.24); opacity: 0.83; transform: rotate(26deg); }
    .sqv6-best__blob--w13 { left: 75.8%; top: -54.4%; width: 3%; height: 115%; background: rgba(255,255,255,0.30); opacity: 0.73; transform: rotate(12deg); }
    .sqv6-best__blob--w14 { left: 0.3%; top: -52.5%; width: 3%; height: 103%; background: rgba(255,236,246,0.26); opacity: 0.56; transform: rotate(-12deg); }
    .sqv6-best__blob--w15 { left: 87.0%; top: 20.8%; width: 3%; height: 87%; background: rgba(255,246,236,0.24); opacity: 0.7; transform: rotate(36deg); }
    .sqv6-best__blob--w16 { left: 84.7%; top: 8.6%; width: 2%; height: 150%; background: rgba(255,255,255,0.30); opacity: 0.79; transform: rotate(-18deg); }

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
    .sqv6-best__smoke .sqv6-best__blob { display: none; }
    .sqv6-best__blob--f1 { left: 69.7%; top: 22.7%; width: 4%; height: 128%; background: rgba(255,255,255,0.22); opacity: 0.6; transform: rotate(22deg); }
    .sqv6-best__blob--f2 { left: 22.8%; top: 33.2%; width: 3%; height: 157%; background: rgba(255,255,255,0.22); opacity: 0.66; transform: rotate(14deg); }
    .sqv6-best__blob--f3 { left: 13.4%; top: 19.4%; width: 2%; height: 131%; background: rgba(255,255,255,0.22); opacity: 0.45; transform: rotate(32deg); }
    .sqv6-best__blob--f4 { left: 33.8%; top: 6.1%; width: 2%; height: 150%; background: rgba(255,255,255,0.22); opacity: 0.56; transform: rotate(14deg); }
    .sqv6-best__blob--f5 { left: 52.8%; top: 27.1%; width: 4%; height: 121%; background: rgba(255,255,255,0.22); opacity: 0.61; transform: rotate(32deg); }
    .sqv6-best__blob--f6 { left: -2.4%; top: 36.3%; width: 3%; height: 130%; background: rgba(255,255,255,0.22); opacity: 0.7; transform: rotate(14deg); }
    .sqv6-best__blob--f7 { left: 30.7%; top: -9.5%; width: 4%; height: 159%; background: rgba(255,255,255,0.22); opacity: 0.5; transform: rotate(14deg); }
    .sqv6-best__blob--f8 { left: 10.2%; top: -33.4%; width: 2%; height: 128%; background: rgba(255,255,255,0.22); opacity: 0.55; transform: rotate(14deg); }
    .sqv6-best__blob--f9 { left: 93.1%; top: 18.6%; width: 4%; height: 116%; background: rgba(255,255,255,0.22); opacity: 0.41; transform: rotate(-14deg); }

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
    /* Phone grid: two columns, the left one dropped so the pair reads staggered
       exactly like the desktop row. Rows need their own bottom gap. */
    .sqv6-best--grid .sqv6-best__slide { padding-top: 0; margin-bottom: 12px; }
    .sqv6-best--grid .sqv6-best__slide:nth-child(2n + 1) { padding-top: 24px; }
    /* Deal Card: 236.47 x 375.67, radius 7.15736, no shadow on the pink. */
    .sqv6-best__slide .sqv6-deal {
        border-radius: 7.15736px;
        box-shadow: none;
    }
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
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv6-best { margin: clamp(64px, 5.556vw, 80px) 0 clamp(40px, 3.889vw, 56px); }
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
        .sqv6-best__blob { filter: blur(90px); }

        .sqv6-best__smoke { opacity: 0.7; }
        .sqv6-best__slide:nth-child(2n) { padding-top: clamp(14px, 1.597vw, 23px); }
        .sqv6-best__title { font-size: clamp(28px, 2.778vw, 40px); }
        .sqv6-best__seeall { font-size: clamp(15px, 1.389vw, 20px); }
    }
</style>

<!-- ============ BEST SELLER ============ -->
<section class="sqv6-best">
  <div class="sqv6-best__bg" aria-hidden="true">
    <span class="sqv6-best__blob sqv6-best__blob--s1"></span>
    <span class="sqv6-best__blob sqv6-best__blob--s2"></span>
    <span class="sqv6-best__blob sqv6-best__blob--s3"></span>
    <span class="sqv6-best__blob sqv6-best__blob--s4"></span>
    <span class="sqv6-best__blob sqv6-best__blob--s5"></span>
    <span class="sqv6-best__blob sqv6-best__blob--s6"></span>
    <span class="sqv6-best__blob sqv6-best__blob--s7"></span>
    <span class="sqv6-best__blob sqv6-best__blob--s8"></span>
    <span class="sqv6-best__blob sqv6-best__blob--p1"></span>
    <span class="sqv6-best__blob sqv6-best__blob--p2"></span>
    <span class="sqv6-best__blob sqv6-best__blob--p3"></span>
    <span class="sqv6-best__blob sqv6-best__blob--p4"></span>
    <span class="sqv6-best__blob sqv6-best__blob--p5"></span>
    <span class="sqv6-best__blob sqv6-best__blob--w sqv6-best__blob--w1"></span>
    <span class="sqv6-best__blob sqv6-best__blob--w sqv6-best__blob--w2"></span>
    <span class="sqv6-best__blob sqv6-best__blob--w sqv6-best__blob--w3"></span>
    <span class="sqv6-best__blob sqv6-best__blob--w sqv6-best__blob--w4"></span>
    <span class="sqv6-best__blob sqv6-best__blob--w sqv6-best__blob--w5"></span>
    <span class="sqv6-best__blob sqv6-best__blob--w sqv6-best__blob--w6"></span>
    <span class="sqv6-best__blob sqv6-best__blob--w sqv6-best__blob--w7"></span>
    <span class="sqv6-best__blob sqv6-best__blob--w sqv6-best__blob--w8"></span>
    <span class="sqv6-best__blob sqv6-best__blob--w sqv6-best__blob--w9"></span>
    <span class="sqv6-best__blob sqv6-best__blob--w sqv6-best__blob--w10"></span>
    <span class="sqv6-best__blob sqv6-best__blob--w sqv6-best__blob--w11"></span>
    <span class="sqv6-best__blob sqv6-best__blob--w sqv6-best__blob--w12"></span>
    <span class="sqv6-best__blob sqv6-best__blob--w sqv6-best__blob--w13"></span>
    <span class="sqv6-best__blob sqv6-best__blob--w sqv6-best__blob--w14"></span>
    <span class="sqv6-best__blob sqv6-best__blob--w sqv6-best__blob--w15"></span>
    <span class="sqv6-best__blob sqv6-best__blob--w sqv6-best__blob--w16"></span>
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
    <span class="sqv6-best__blob sqv6-best__blob--f1"></span>
    <span class="sqv6-best__blob sqv6-best__blob--f2"></span>
    <span class="sqv6-best__blob sqv6-best__blob--f3"></span>
    <span class="sqv6-best__blob sqv6-best__blob--f4"></span>
    <span class="sqv6-best__blob sqv6-best__blob--f5"></span>
    <span class="sqv6-best__blob sqv6-best__blob--f6"></span>
    <span class="sqv6-best__blob sqv6-best__blob--f7"></span>
    <span class="sqv6-best__blob sqv6-best__blob--f8"></span>
    <span class="sqv6-best__blob sqv6-best__blob--f9"></span>
  </div>
</section>
