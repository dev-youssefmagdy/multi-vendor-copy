{{--
  Flash sale card — Elora v4
  Figma desktop: 16:10603, 16:10605 | mobile: inside 16:7485

  Layout: horizontal — image left (fixed width) + info right (flex-1)
  Desktop: h-148px, image w-156px
  Mobile: h-120px, image w-100px (tighter via .flash-card-mobile)

  $p keys: id, url, favData, image, name, weight, price, oldPrice, discount, rating
--}}
<div class="flash-card-wrap">
  <a href="{{ $p['url'] ?? '#' }}"
     class="flash-card"
     style="text-decoration:none">

    {{-- ── Image section ──────────────────────────────────────────── --}}
    <div class="flash-card-img-wrap">

      {{-- Product image --}}
      <img
        src="{{ $p['image'] }}"
        alt="{{ $p['name'] }}"
        class="flash-card-img"
        loading="lazy"
      />

      {{-- Discount / sold badge — top-right corner --}}
      @if ($p['discount'])
        <span class="flash-card-badge">
          {{ $p['discount'] }}
        </span>
      @endif

      {{-- Favorite button --}}
      <button
        type="button"
        class="elora-v4-heart-btn flash-card-fav"
        onclick="event.preventDefault(); event.stopPropagation(); eloraV4ToggleFavorite(this)"
        data-fav='{{ $p['favData'] ?? '{}' }}'
        aria-label="{{ __('Add to favorites') }}"
      >
        <img src="{{ asset('elora-4/assets/icons/heart.svg') }}" alt="" class="size-[14px] lg:size-[18px]" />
      </button>

    </div>

    {{-- ── Info section ────────────────────────────────────────────── --}}
    <div class="flash-card-info">

      {{-- Name + weight --}}
      <div class="flash-card-name-row">
        <p class="flash-card-name line-clamp-2">{{ $p['name'] }}</p>
        @if ($p['weight'])
          <p class="flash-card-weight">{{ $p['weight'] }}</p>
        @endif
      </div>

      {{-- Rating --}}
      <div class="flash-card-rating-row">
        <img
          src="{{ asset('elora-4/assets/icons/star-rating.svg') }}"
          alt=""
          class="h-[9px] w-[63px] lg:h-[10px] lg:w-[71px] shrink-0"
        />
        <span class="flash-card-rating-text">{{ $p['rating'] }}</span>
      </div>

      {{-- Price row --}}
      <div class="flash-card-price-row">
        <span class="flash-card-price">{{ $p['price'] }}</span>
        @if ($p['oldPrice'])
          <span class="flash-card-old-price">{{ $p['oldPrice'] }}</span>
        @endif
      </div>

      {{-- Add to cart button --}}
      <button
        type="button"
        class="flash-card-cart"
        wire:click.prevent="addToCart({{ $p['id'] ?? 0 }})"
        onclick="event.stopPropagation()"
        aria-label="{{ __('Add to cart') }}"
      >
        <img src="{{ asset('elora-4/assets/icons/cart.svg') }}" alt="" class="size-[16px] lg:size-[20px] invert" />
        <span class="flash-card-cart-label">{{ __('Add') }}</span>
      </button>

    </div>

  </a>
</div>
