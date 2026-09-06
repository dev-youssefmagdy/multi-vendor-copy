{{-- Figma "Mobile Card" (496.21x156.48). $p keys: id, url, image, name, weight, desc,
     rating, price, oldPrice, discount, sold, delivery, stock.
     Styles live in the parent section under the .sqv3-trend-* namespace. --}}
<div class="sqv3-trend-card" wire:key="trending-v3-{{ $p['id'] }}">
  <a href="{{ $p['url'] }}" class="sqv3-trend-card__media">
    @if ($p['image'])
      <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="sqv3-trend-card__img" />
    @endif
    @if (!empty($p['sold']))
      <span class="sqv3-trend-card__sale">{{ $p['sold'] }}</span>
    @endif
    <button type="button" wire:click.stop.prevent="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to favorites') }}" class="sqv3-trend-card__heart">
      <img src="{{ asset('souqify-3/assets/icons/icon-heart.svg') }}" alt="" />
    </button>
    <button type="button" wire:click.stop.prevent="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to cart') }}" class="sqv3-trend-card__cart">
      <img src="{{ asset('souqify-3/assets/icons/icon-cart-teal.svg') }}" alt="" />
    </button>
  </a>

  <div class="sqv3-trend-card__info">
    <div class="sqv3-trend-card__head">
      <div class="sqv3-trend-card__titlerow">
        <a href="{{ $p['url'] }}" class="sqv3-trend-card__name">{{ $p['name'] }}</a>
        @if (!empty($p['weight']))
          <span class="sqv3-trend-card__weight">{{ $p['weight'] }}</span>
        @endif
      </div>
      @if (!empty($p['desc']))
        <p class="sqv3-trend-card__desc">{{ $p['desc'] }}</p>
      @endif
    </div>

    <div class="sqv3-trend-card__meta">
      <div class="sqv3-trend-card__rating">
        <img src="{{ asset('souqify-3/assets/icons/star-rating.svg') }}" alt="" class="sqv3-trend-card__stars" />
        <span class="sqv3-trend-card__ratingtext">{{ $p['rating'] }}</span>
      </div>
      <div class="sqv3-trend-card__prices">
        <span class="sqv3-trend-card__price">{{ $p['price'] }}</span>
        @if (!empty($p['oldPrice']))
          <span class="sqv3-trend-card__oldprice">{{ $p['oldPrice'] }}</span>
        @endif
        @if (!empty($p['discount']))
          <span class="sqv3-trend-card__discount">{{ $p['discount'] }}</span>
        @endif
      </div>
    </div>

    <div class="sqv3-trend-card__status">
      <div class="sqv3-trend-card__statusrow">
        <img src="{{ asset('souqify-3/assets/icons/icon-truck-delivery-green.svg') }}" alt="" class="sqv3-trend-card__truck" />
        <span class="sqv3-trend-card__statustext">{{ $p['delivery'] }}</span>
      </div>
    </div>
  </div>
</div>
