{{-- Figma "Deal Card" (319.97x511.14). Styles live in deal_card_styles under the
     .sqv3-deal-* namespace. $p keys: id, slug, url, image, name, weight, rating,
     ratingValue, price, rawPrice, oldPrice, discount, badge, stock. --}}
@php
    $__favData = json_encode([
        'slug' => $p['slug'] ?? null,
        'name' => $p['name'] ?? null,
        'price' => $p['rawPrice'] ?? null,
        'old_price' => $p['oldPrice'] ?? null,
        'discount' => $p['discount'] ?? null,
        'rating' => $p['ratingValue'] ?? 0,
        'image' => $p['image'] ?? null,
        'url' => $p['url'] ?? null,
        'added' => time(),
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
@endphp
<div class="sqv3-deal" wire:key="flash-v3-{{ $p['id'] }}">
  <a href="{{ $p['url'] }}" class="sqv3-deal__media">
    @if ($p['image'])
      <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="sqv3-deal__img" />
    @endif

    @if (!empty($p['badge']))
      <span class="sqv3-deal__badge">{{ $p['badge'] }}</span>
    @endif

    <button type="button" onclick="event.preventDefault();event.stopPropagation();souqifyToggleFavorite(this)" data-slug="{{ $p['slug'] ?? '' }}" data-fav='{{ $__favData }}' aria-label="{{ __('Add to favorites') }}" class="sqv3-deal__heart souqify-fav-btn">
      <img src="{{ asset('souqify-3/assets/icons/icon-heart.svg') }}" alt="" />
    </button>

    <button type="button" wire:click.stop.prevent="addToCart({{ $p['id'] }})" wire:loading.attr="disabled" wire:target="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to cart') }}" class="sqv3-deal__cart">
      <img src="{{ asset('souqify-3/assets/icons/icon-cart-teal.svg') }}" alt="" />
    </button>

    @if (!empty($p['stock']))
      <span class="sqv3-deal__ribbon">
        <img src="{{ asset('souqify-3/assets/icons/icon-cart-x.svg') }}" alt="" class="sqv3-deal__ribbon-icon" />
        <span class="sqv3-deal__ribbon-text">{{ $p['stock'] }}</span>
      </span>
    @endif
  </a>

  <div class="sqv3-deal__body">
    <div class="sqv3-deal__titlerow">
      <a href="{{ $p['url'] }}" class="sqv3-deal__name">{{ $p['name'] }}</a>
      @if (!empty($p['weight']))
        <span class="sqv3-deal__weight">{{ $p['weight'] }}</span>
      @endif
    </div>

    @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
    <div class="sqv3-deal__rating">
      <div class="sqv3-deal__stars" style="display:flex;align-items:center;gap:1px">
        @for ($__i = 1; $__i <= 5; $__i++)
          <svg style="height:100%;width:auto" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
        @endfor
      </div>
      <span class="sqv3-deal__ratingtext">({{ $__rv }})</span>
    </div>

    <div class="sqv3-deal__pricerow">
      <span class="sqv3-deal__prices">
        <span class="sqv3-deal__price">{{ $p['price'] }}</span>
        @if (!empty($p['oldPrice']))
          <span class="sqv3-deal__oldprice">{{ $p['oldPrice'] }}</span>
        @endif
      </span>
      @if (!empty($p['discount']))
        <span class="sqv3-deal__discount"
          @if (!empty($p['discountBg']))style="background:{{ $p['discountBg'] }}; color:{{ $p['discountColor'] ?? '#121212' }}"@endif>{{ $p['discount'] }}</span>
      @endif
    </div>
  </div>
</div>
