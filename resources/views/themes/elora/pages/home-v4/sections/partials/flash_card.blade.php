{{--
  Flash sale card — Elora v4
  Figma desktop: 16:10603, 16:10605 | mobile: inside 16:7485

  Layout: horizontal — image left (fixed width) + info right (flex-1)
  Desktop: h-148px, image w-156px
  Mobile: h-120px, image w-100px (tighter via .flash-card-mobile)

  $p keys: id, url, favData, image, name, weight, price, oldPrice, discount, rating
--}}
@php $__deliveryDate = \Carbon\Carbon::now()->addDays(3)->translatedFormat('d F'); @endphp
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

      {{-- Add to cart button --}}
      <button
        type="button"
        class="flash-card-cart"
        wire:click.prevent="addToCart({{ $p['id'] ?? 0 }})"
        onclick="event.stopPropagation()"
        aria-label="{{ __('Add to cart') }}"
      >
        <img src="{{ asset('elora-4/assets/icons/add-to-cart.svg') }}" alt="" class="flash-card-cart-icon" />
      </button>

    </div>

    {{-- ── Info section ────────────────────────────────────────────── --}}
    <div class="flash-card-info">

      {{-- Name + weight --}}
      <div class="flash-card-name-row">
        <p class="flash-card-name line-clamp-1">{{ $p['name'] }}</p>
        @if ($p['weight'])
          <p class="flash-card-weight">{{ $p['weight'] }}</p>
        @endif
      </div>

      {{-- Rating --}}
      @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
      <div class="flash-card-rating-row">
        <div class="flex items-center gap-[1px]" style="height:9px">
          @for ($__i = 1; $__i <= 5; $__i++)
            <svg style="height:9px;width:9px" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
          @endfor
        </div>
        <span class="flash-card-rating-text">({{ $__rv }})</span>
      </div>

      {{-- Price row --}}
      <div class="flash-card-price-row">
        <span class="flash-card-price">{{ $p['price'] }}</span>
        @if ($p['oldPrice'])
          <span class="flash-card-old-price">{{ $p['oldPrice'] }}</span>
        @endif
        @if ($p['discount'])
          <span class="flash-card-discount">{{ $p['discount'] }}</span>
        @endif
      </div>

      {{-- Delivery estimate --}}
      <div class="flash-card-delivery-row">
        <img src="{{ asset('elora-4/assets/icons/truck-delivery.svg') }}" alt="" class="flash-card-delivery-icon" />
        <span class="flash-card-delivery-text">{{ __('Delivered by') }} {{ $__deliveryDate }}</span>
      </div>

    </div>

  </a>
</div>
