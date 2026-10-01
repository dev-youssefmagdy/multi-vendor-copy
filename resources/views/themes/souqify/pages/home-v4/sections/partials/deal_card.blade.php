{{-- Figma "Deal Card". Sections supply $style (position/size) and, optionally,
     $cartIcon - the cart glyph is stroked in the section's accent colour.
     Internal sizes use cqw against the card's own width so the whole card scales
     with its container. $p keys: id, url, image, name, weight, rating, price,
     oldPrice, discount, discountBg, discountColor, badge, stock. --}}
<a href="{{ $p['url'] }}" class="sqv4-deal" style="{{ $style }}" wire:key="flash-v4-{{ $p['id'] }}">
  <div class="sqv4-deal__media">
    @if ($p['image'])
      <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="sqv4-deal__img" />
    @endif

    <span class="sqv4-deal__badge">{{ $p['badge'] }}</span>

    <span class="sqv4-deal__heart" onclick="event.preventDefault(); event.stopPropagation(); souqifyToggleFavorite(this)" data-slug="{{ $p['slug'] ?? '' }}" data-fav='{{ $p['favData'] ?? '{}' }}'>
      <img src="{{ asset('souqify-3/assets/icons/icon-heart.svg') }}" alt="" />
    </span>

    <span class="sqv4-deal__cart"
      @if (empty($p['outOfStock']))
        wire:click="addToCart({{ $p['id'] }})" wire:loading.attr="disabled" wire:target="addToCart({{ $p['id'] }})"
        onclick="event.preventDefault(); event.stopPropagation();"
      @else
        aria-disabled="true" onclick="event.preventDefault(); event.stopPropagation();"
      @endif>
      <img src="{{ asset('souqify-3/assets/icons/' . ($cartIcon ?? 'icon-cart-purple.svg')) }}" alt="" />
    </span>

    <span class="sqv4-deal__ribbon">
      <img src="{{ asset('souqify-3/assets/icons/icon-cart-x.svg') }}" alt="" class="sqv4-deal__ribbon-icon" />
      <span class="sqv4-deal__ribbon-text">{{ $p['stock'] }}</span>
    </span>
  </div>

  <div class="sqv4-deal__body">
    <div class="sqv4-deal__titlerow">
      <span class="sqv4-deal__name">{{ $p['name'] }}</span>
      @if (!empty($p['weight']))
        <span class="sqv4-deal__weight">{{ $p['weight'] }}</span>
      @endif
    </div>

    @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
    <div class="sqv4-deal__rating">
      <div class="sqv4-deal__stars" style="display:flex;align-items:center;gap:1px">
        @for ($__i = 1; $__i <= 5; $__i++)
          <svg style="height:100%;width:auto" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
        @endfor
      </div>
      <span class="sqv4-deal__ratingtext">({{ $__rv }})</span>
    </div>

    <div class="sqv4-deal__pricerow">
      <span class="sqv4-deal__prices">
        <span class="sqv4-deal__price">{{ $p['price'] }}</span>
        @if (!empty($p['oldPrice']))
          <span class="sqv4-deal__oldprice">{{ $p['oldPrice'] }}</span>
        @endif
      </span>
      @if (!empty($p['discount']))
        <span class="sqv4-deal__discount" style="background:{{ $p['discountBg'] }}; color:{{ $p['discountColor'] }}">{{ $p['discount'] }}</span>
      @endif
    </div>
  </div>
</a>
