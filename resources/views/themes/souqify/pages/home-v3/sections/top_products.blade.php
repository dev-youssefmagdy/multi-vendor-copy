@php
    // Figma cycles three discount-chip palettes across the Best Seller cards.
    $__chipPalettes = [
        ['bg' => '#DE1709', 'color' => '#FDFDFD'],
        ['bg' => '#FF570F', 'color' => '#FDFDFD'],
        ['bg' => '#FFB00A', 'color' => '#121212'],
    ];

    $__bestSellerCards = collect($bestSelling ?? [])->take(6)->values()->map(function ($product, $index) use ($symbol, $rate, $__chipPalettes) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();
        $chip = $__chipPalettes[$index % count($__chipPalettes)];

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'ratingValue' => $rating,
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'rawPrice' => round((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
            'discountBg' => $chip['bg'],
            'discountColor' => $chip['color'],
            // Design-only fields with no backing data yet.
            'weight' => null,
            'badge' => '🔥 ' . __('Trending Now'),
            'stock' => __('Only 5 left - Hurry up'),
        ];
    });

    $__bestSellerMobile = $__bestSellerCards->take(3);
@endphp

{{-- Figma: Frame 1984080343 (1440x632.15) - 24px 56px padding, #199387 with an
     abstract PNG blended hard-light. 50px title + 430.15px card row + the
     Explore all button, 24px apart. Card = the same Deal Card as Flash Sale, at
     269.05x430.15 with a 20.18px gap (Frame 1984079779).
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv3-best {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 24px 16px;
        gap: 16px;
        background: var(--color-souqify-teal);
        overflow: hidden;
    }
    .sqv3-best__texture {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.3;
        mix-blend-mode: hard-light;
        pointer-events: none;
    }
    .sqv3-best__inner {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 16px;
        width: 100%;
        min-width: 0;
    }
    .sqv3-best__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 24px;
        line-height: 1.25;              /* 50 / 40 */
        color: #FFFFFF;
        margin: 0;
    }

    /* ---------- Draggable fan cascade ----------
       Same technique as Green Edition's Best Seller carousel (see
       mountFanCascadeCarousel() in carousels-v3.js): every slide is
       absolutely positioned from a fixed geometry table indexed by distance
       from the active slide, so dragging shifts which product sits in the
       biggest/leftmost slot while the rest cascade down in size behind it. */
    .sqv3-best__swiper {
        /* overflow:visible lets the fan's cards bleed past the swiper's own
           (much smaller) auto-width box. */
        overflow: visible;
        padding: 0 0 24px;
        cursor: grab;
        width: 100% !important;
    }
    @media (max-width: 1023.98px) {
        /* The mobile cascade table (SQV3_CASCADE_POSITIONS_MOBILE) spans
           ~375.75px, sized for the section's full width. Inside the
           section's 16px side padding that only leaves 343px, so the last
           card's peek slice got clipped mid-card (through its heart/cart
           badges) instead of at a clean edge. Bleeding the swiper out past
           the padding on both sides gives the cascade its full width back. */
        .sqv3-best__swiper {
            width: calc(100% + 32px) !important;
            margin-left: -16px;
            margin-right: -16px;
        }
    }
    .sqv3-best__group {
        position: relative;
        width: 100%;
    }
    /* Every slide is this ONE fixed base size (matching the biggest/active
       card exactly); mountFanCascadeCarousel applies
       `transform: translate(left, top) scale(ratio)` per slide to produce
       every other cascade depth from it, so content never gets crammed into
       a shrunk box - the whole card scales as one unit. */
    .sqv3-best__deal-base {
        position: absolute;
        top: 0;
        left: 0;
        width: 154.5px;
        height: 223.1px;
        transform-origin: top left;
    }
    @media (min-width: 1024px) {
        .sqv3-best__deal-base {
            width: 295.45px;
            height: 472.89px;
        }
    }
    .sqv3-best__deal-base .sqv3-deal {
        position: static;
        width: 100%;
        height: 100%;
        filter: drop-shadow(0px 0px 30px rgba(0, 0, 0, 0.18));
    }

    /* ---------- Pagination ---------- */
    .sqv3-best__dots {
        display: block;
        text-align: center;
        width: 100%;
        padding: 0;
        margin: 0;
        height: 8px;
        font-size: 0;
        line-height: 0;
    }
    .sqv3-best__dots .best-seller-dot {
        display: inline-block;
        vertical-align: top;
        margin: 0 1.5px;
        width: 8px;
        height: 8px;
        background: rgba(255, 255, 255, 0.4);
        border: 0;
        border-radius: 29px;
        padding: 0;
        opacity: 1;
        transition: width 0.2s ease, background-color 0.2s ease;
        cursor: pointer;
    }
    .sqv3-best__dots .best-seller-dot.is-active {
        width: 24px;
        background: #FFFFFF;
    }
    .sqv3-best__empty {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        padding: 24px 0;
        color: rgba(255, 255, 255, 0.8);
    }

    /* ---------- Explore all ---------- */
    .sqv3-best__cta {
        box-sizing: border-box;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 8px;
        width: 100%;
        max-width: 304px;
        height: 44px;
        background: #FFFFFF;
        border: 1px solid #FFFFFF;
        border-radius: 34px;
        text-decoration: none;
    }
    .sqv3-best__cta-label {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 16px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: var(--color-souqify-teal);
    }

    @media (min-width: 1024px) {
        .sqv3-best {
            /* The comp's content column is 1328px inside a 1440 canvas. Capping it
               keeps slidesPerView from inflating the cards on wider displays;
               max() still leaves the Figma 56px gutter at exactly 1440. */
            padding: 24px max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv3-best__inner {
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv3-best__title {
            /* 40px / 50px line-height, weight 800 */
            font-size: clamp(28px, 2.778vw, 40px);
        }
        .sqv3-best__cta {
            /* 304 x 56 */
            max-width: clamp(240px, 21.111vw, 304px);
            height: clamp(44px, 3.889vw, 56px);
        }
        .sqv3-best__cta-label {
            font-size: clamp(16px, 1.389vw, 20px);
        }
    }
</style>

@include('themes.souqify.pages.home-v3.sections.partials.deal_card_styles')

<!-- ============ BEST SELLER ============ -->
<section class="sqv3-best" wire:ignore>
  <img src="{{ asset('souqify-2/assets/images/best-seller-texture-mobile.png') }}" alt="" class="sqv3-best__texture lg:hidden" />
  <img src="{{ asset('souqify-2/assets/images/best-seller-texture-desktop.png') }}" alt="" class="sqv3-best__texture hidden lg:block" />

  <div class="sqv3-best__inner">
    <h2 class="sqv3-best__title">{{ __('Best Seller') }}</h2>

    @if ($__bestSellerCards->isNotEmpty())
      {{-- Same draggable fan-cascade carousel as Green Edition's Best Seller
           (see mountFanCascadeCarousel() in carousels-v3.js). --}}
      <div class="swiper sqv3-best__swiper lg:!hidden w-full">
        <div class="swiper-wrapper sqv3-best__group" id="bestSellerMobileWrapper">
          @foreach ($__bestSellerMobile as $p)
            <div class="swiper-slide sqv3-best__deal-base" style="position:absolute; top:0; left:0;" wire:key="best-seller-mobile-v3-{{ $p['id'] }}">
              @include('themes.souqify.pages.home-v3.sections.partials.deal_card', ['p' => $p])
            </div>
          @endforeach
        </div>
      </div>
      <div class="sqv3-best__dots lg:!hidden" id="bestSellerMobileDots"></div>

      <div class="swiper sqv3-best__swiper !hidden lg:!block w-full">
        <div class="swiper-wrapper sqv3-best__group" id="bestSellerDesktopWrapper">
          @foreach ($__bestSellerCards as $p)
            <div class="swiper-slide sqv3-best__deal-base" style="position:absolute; top:0; left:0;" wire:key="best-seller-desktop-v3-{{ $p['id'] }}">
              @include('themes.souqify.pages.home-v3.sections.partials.deal_card', ['p' => $p])
            </div>
          @endforeach
        </div>
      </div>
      <div class="sqv3-best__dots !hidden lg:!block" id="bestSellerDesktopDots"></div>

      <a href="{{ route('tenant.storefront.best-selling') }}" class="sqv3-best__cta">
        <span class="sqv3-best__cta-label">{{ __('Explore all') }}</span>
      </a>
    @else
      <p class="sqv3-best__empty">{{ __('No best sellers yet.') }}</p>
    @endif
  </div>
</section>
