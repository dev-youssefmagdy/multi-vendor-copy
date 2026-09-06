@php
    $__bestSellerCards = collect($bestSelling ?? [])->take(6)->values()->map(function ($product, $index) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();
        // Figma cycles three discount-chip palettes across the fanned cards.
        $chip = [
            ['bg' => '#FFB00A', 'color' => '#121212'],
            ['bg' => '#DE1709', 'color' => '#FDFDFD'],
            ['bg' => '#FF570F', 'color' => '#FDFDFD'],
        ][$index % 3];

        return [
            'id' => $product->id,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'tag' => '🔥 ' . __('Trending Now'),
            'cartIcon' => 'trending-cart-purple.svg',
            'stock' => __('Only 5 left - Hurry up'),
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
            'discountBg' => $chip['bg'],
            'discountColor' => $chip['color'],
        ];
    });
@endphp

{{-- Figma: Frame 1984080385 (1440x681) - a full-bleed #EC910A panel whose top and
     bottom edges are scalloped (fifteen 101.01-wide rects with a 0/86 radius),
     a 50px header, then Group 58: six 228.76x366.14 Deal Cards fanned and
     overlapped, rotations cycling -11.98deg / 0 / 8.97deg.
     Values are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv5-best {
        /* The comp keeps the card's price and tag purple inside this panel. */
        --sqv5-deal-accent: #8B03BD;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 40px 16px;
        gap: 16px;
        /* The panel has no fill of its own: the scallop IS the union of the
           orange columns below. */
        background: transparent;
        isolation: isolate;
    }
    /* Rectangles 5967-5981: fifteen 101.01-wide full-height columns, each with a
       0/86 radius (rounded top-right and bottom-left only), stepped 95.64 apart
       so they overlap. Their union is the scalloped panel - the sharp point of
       one tooth meets the rounded shoulder of the next, which a symmetric
       semicircle mask cannot reproduce. */
    .sqv5-best__bg {
        position: absolute;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        overflow: hidden;
    }
    .sqv5-best__bg span {
        position: absolute;
        top: 0;
        bottom: 0;
        /* 101.01 / 1440 */
        width: 7.015%;
        background: var(--color-primary);
        /* 86px on the 1440 canvas */
        border-radius: 0 clamp(34px, 5.972vw, 86px);
    }

    .sqv5-best__head {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    .sqv5-best__title {
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 24px;
        line-height: 1.25;              /* 50 / 40 */
        color: #FFFFFF;
        margin: 0;
    }
    .sqv5-best__seeall {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 14px;
        line-height: 1.25;              /* 25 / 20 */
        letter-spacing: 0.5px;
        color: #FFFFFF;
        white-space: nowrap;
    }

    /* ---------- Fanned card row ---------- */
    .sqv5-best__row {
        position: relative;
        z-index: 1;
        width: 100%;
        min-width: 0;
        /* The rotated cards need vertical room: the swiper clips its own box, so
           the padding keeps the tilted corners inside it. */
        /* The cards are tilted, so the swiper - which clips its own box - needs
           room on every side or the first card loses its left corner. */
        padding: 28px 20px;
        overflow: hidden;
    }
    .sqv5-best__row .swiper-wrapper { align-items: center; }
    .sqv5-best__slide { height: auto; }
    /* No shadow in this section: the tilted cards overlap, and any shadow pools
       into a dark smear on the orange instead of reading as depth. */
    .sqv5-best__slide .sqv5-deal { box-shadow: none; }

    /* Group 58 fans the cards at every size: they overlap and alternate
       rotation. The cycle is per slide, so it survives any product count. */
    .sqv5-best__slide:nth-child(3n + 1) { transform: rotate(-11.98deg); }
    .sqv5-best__slide:nth-child(3n + 2) { transform: rotate(0deg); }
    .sqv5-best__slide:nth-child(3n + 3) { transform: rotate(8.97deg); }

    @media (min-width: 1024px) {
        .sqv5-best {
            /* 24px 56px padding on the 1440 canvas, plus the room the scallop and
               the rotated cards need. */
            padding: clamp(40px, 4.583vw, 66px) max(clamp(24px, 3.889vw, 56px), calc((100% - 1328px) / 2));
            gap: clamp(16px, 1.667vw, 24px);
        }
        .sqv5-best__title { font-size: clamp(28px, 2.778vw, 40px); }
        .sqv5-best__seeall { font-size: clamp(15px, 1.389vw, 20px); }
    }
</style>

<!-- ============ BEST SELLER ============ -->
<section class="sqv5-best mx-[8px] lg:mx-[16px]">
  <span class="sqv5-best__bg" aria-hidden="true">
    {{-- 95.64 / 1440 = 6.642% step; 16 columns cover the full 1440 width. --}}
    @for ($__i = 0; $__i < 16; $__i++)
      <span style="left: {{ round($__i * 6.642, 3) }}%"></span>
    @endfor
  </span>

  <div class="sqv5-best__head">
    <h2 class="sqv5-best__title">{{ __('Best Seller') }}</h2>
    <a href="{{ route('tenant.storefront.best-selling') }}" class="sqv5-best__seeall">{{ __('see all') }}</a>
  </div>

  @if ($__bestSellerCards->isNotEmpty())
    <div class="swiper bestseller-swiper sqv5-best__row">
      <div class="swiper-wrapper" id="bestSellerWrapper">
        @foreach ($__bestSellerCards as $p)
          <div class="swiper-slide sqv5-best__slide" wire:key="best-seller-v5-{{ $p['id'] }}">
            @include('themes.souqify.pages.home-v5.sections.partials.deal_card', ['p' => $p])
          </div>
        @endforeach
      </div>
    </div>
  @else
    <p class="relative text-white/80 text-sm py-6">{{ __('No best sellers yet.') }}</p>
  @endif
</section>
