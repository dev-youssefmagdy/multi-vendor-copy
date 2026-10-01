{{-- Figma "Mobile Card" (463.76x205.46). $p keys: id, url, image, name, weight, desc,
     rating, price, oldPrice, discount, sold, ordered, progress, delivery, stock.
     Styles live in the parent section under the .sqv4-trend-* namespace. --}}
<div class="swiper-slide sqv4-trend-card" wire:key="trending-v4-{{ $p['id'] }}">
  <a href="{{ $p['url'] }}" class="sqv4-trend-card__media">
    @if ($p['image'])
      <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="sqv4-trend-card__img" />
    @endif
    @if (!empty($p['sold']))
      <span class="sqv4-trend-card__sale">{{ $p['sold'] }}</span>
    @endif
    <button type="button" onclick="event.preventDefault(); event.stopPropagation(); souqifyToggleFavorite(this)" data-slug="{{ $p['slug'] ?? '' }}" data-fav='{{ $p['favData'] ?? '{}' }}' aria-label="{{ __('Add to favorites') }}" class="sqv4-trend-card__heart">
      <img src="{{ asset('souqify-3/assets/icons/icon-heart.svg') }}" alt="" />
    </button>
    <button type="button"
      @if (empty($p['outOfStock']))
        wire:click.stop.prevent="addToCart({{ $p['id'] }})" wire:loading.attr="disabled" wire:target="addToCart({{ $p['id'] }})"
      @else
        disabled onclick="event.preventDefault(); event.stopPropagation();"
      @endif
      aria-label="{{ __('Add to cart') }}" class="sqv4-trend-card__cart">
      <img src="{{ asset('souqify-3/assets/icons/icon-cart.svg') }}" alt="" />
    </button>
  </a>

  <div class="sqv4-trend-card__info">
    <div class="sqv4-trend-card__head">
      <div class="sqv4-trend-card__titlerow">
        <a href="{{ $p['url'] }}" class="sqv4-trend-card__name">{{ $p['name'] }}</a>
        @if (!empty($p['weight']))
          <span class="sqv4-trend-card__weight">{{ $p['weight'] }}</span>
        @endif
      </div>
      @if (!empty($p['desc']))
        <p class="sqv4-trend-card__desc">{{ $p['desc'] }}</p>
      @endif
    </div>

    <div class="sqv4-trend-card__progress">
      <div class="sqv4-trend-card__track">
        <span class="sqv4-trend-card__fill" style="width:{{ $p['progress'] }}%"></span>
      </div>
      <p class="sqv4-trend-card__ordered">{{ $p['ordered'] }}</p>
    </div>

    <div class="sqv4-trend-card__meta">
      @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
      <div class="sqv4-trend-card__rating">
        <div class="sqv4-trend-card__stars" style="display:flex;align-items:center;gap:1px">
          @for ($__i = 1; $__i <= 5; $__i++)
            <svg style="height:100%;width:auto" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
          @endfor
        </div>
        <span class="sqv4-trend-card__ratingtext">({{ $__rv }})</span>
      </div>
      <div class="sqv4-trend-card__prices">
        <span class="sqv4-trend-card__price">{{ $p['price'] }}</span>
        @if (!empty($p['oldPrice']))
          <span class="sqv4-trend-card__oldprice">{{ $p['oldPrice'] }}</span>
        @endif
        @if (!empty($p['discount']))
          <span class="sqv4-trend-card__discount">{{ $p['discount'] }}</span>
        @endif
      </div>
    </div>

    <div class="sqv4-trend-card__status">
      <div class="sqv4-trend-card__statusrow">
        <img src="{{ asset('souqify-3/assets/icons/icon-truck-delivery-green.svg') }}" alt="" class="sqv4-trend-card__truck" />
        <span class="sqv4-trend-card__statustext">{{ $p['delivery'] }}</span>
      </div>
    </div>
  </div>
</div>
