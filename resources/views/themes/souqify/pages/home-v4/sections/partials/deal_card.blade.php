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

    <span class="sqv4-deal__heart">
      <img src="{{ asset('souqify-3/assets/icons/icon-heart.svg') }}" alt="" />
    </span>

    <span class="sqv4-deal__cart">
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

    <div class="sqv4-deal__rating">
      <img src="{{ asset('souqify-3/assets/icons/star-rating.svg') }}" alt="" class="sqv4-deal__stars" />
      <span class="sqv4-deal__ratingtext">{{ $p['rating'] }}</span>
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
