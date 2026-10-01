{{-- Expects $p: url, image, name, weight, desc, badge, badgeBg, badgeText, progress, ordered, rating, price, oldPrice, discount, delivered --}}
<div class="swiper-slide product-card">
  <a href="{{ $p['url'] ?? '#' }}" class="flex flex-row w-full h-full overflow-hidden">
    {{-- Media --}}
    <div class="card-media">
      <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="product-image" />

      <div class="top-overlay">
        <button
          type="button"
          aria-label="{{ __('Add to favorites') }}"
          @if (!empty($p['favData']))
            onclick="event.preventDefault(); eloraHeartToggle(this)"
            data-fav="{{ $p['favData'] }}"
            data-logged-in="{{ auth()->guard('storefront')->check() ? 'true' : 'false' }}"
            data-product-id="{{ $p['id'] ?? '' }}"
          @endif
          class="heart-btn flex items-center justify-center border-0 cursor-pointer"
          style="box-shadow: 0px 2.5px 2.5px rgba(0, 0, 0, 0.15)"
        >
          <img src="{{ asset('elora-1/assets/icons/heart.svg') }}" alt="" class="icon" />
        </button>
        @if (!empty($p['badge']))
          <span
            class="sale-badge inline-flex items-center justify-center whitespace-nowrap font-normal"
            style="letter-spacing: 0.28px; {{ !empty($p['badgeBg']) ? 'background:' . $p['badgeBg'] . ';' : '' }} {{ !empty($p['badgeText']) ? 'color:' . $p['badgeText'] . ';' : '' }}"
          >{{ $p['badge'] }}</span>
        @endif
      </div>

      <div class="bottom-overlay">
        @if (!empty($p['isOutOfStock']))
          <div role="button" aria-disabled="true" onclick="event.preventDefault()" class="cart-btn flex items-center justify-center cursor-not-allowed">
            <img src="{{ asset('elora-1/assets/icons/cart-plus.svg') }}" alt="{{ __('Add to cart') }}" class="icon opacity-40" />
          </div>
        @elseif (!empty($p['hasMultipleVariants']))
          <div role="button" tabindex="0"
            onclick="event.preventDefault(); openVariantModal({{ $p['id'] ?? 'null' }}, {{ \Illuminate\Support\Js::from($p['fullName'] ?? ($p['name'] ?? '')) }}, {{ \Illuminate\Support\Js::from($p['variantModalData'] ?? []) }})"
            class="cart-btn flex items-center justify-center">
            <img src="{{ asset('elora-1/assets/icons/cart-plus.svg') }}" alt="{{ __('Add to cart') }}" class="icon" />
          </div>
        @else
          <div role="button" tabindex="0" wire:click.prevent="addToCart({{ $p['id'] ?? 'null' }})" onclick="event.preventDefault()" class="cart-btn flex items-center justify-center">
            <img src="{{ asset('elora-1/assets/icons/cart-plus.svg') }}" alt="{{ __('Add to cart') }}" class="icon" />
          </div>
      </div>
    </div>

    {{-- Details --}}
    <div class="card-content items-start justify-center min-h-0">
      <div class="flex flex-col items-start w-full" style="gap: 2.5px">
        <div class="flex flex-row items-center justify-between w-full">
          <p class="product-title font-medium truncate min-w-0" style="letter-spacing: 0.31px; color: #121212; margin: 0">{{ $p['name'] }}</p>
          @if (!empty($p['weight']))
            <p class="weight-tag shrink-0 whitespace-nowrap font-normal" style="letter-spacing: 0.31px; color: var(--color-accent-purple)">{{ $p['weight'] }}</p>
          @endif
        </div>
        @if (!empty($p['desc']))
          <p class="product-subtitle w-full font-normal truncate" style="letter-spacing: 0.31px; color: var(--color-text-subtitle)">{{ $p['desc'] }}</p>
        @endif
      </div>

      @if (isset($p['progress']))
        <div class="flex flex-col items-start w-full" style="gap: 1px">
          <div class="progress-bar relative w-full overflow-hidden" style="background: var(--color-stroke); border-radius: 24px">
            <div class="absolute top-0 start-0 h-full" style="background: var(--color-accent-purple); width: {{ $p['progress'] }}%; border-radius: 24px"></div>
          </div>
          @if (!empty($p['ordered']))
            <p class="progress-text font-normal" style="letter-spacing: 0.31px; color: var(--color-accent-purple)">{{ $p['ordered'] }}</p>
          @endif
        </div>

      <div class="flex flex-col items-start" style="gap: 2.5px">
        @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
        <div class="flex flex-row items-center" style="gap: 5px">
          <div class="flex items-center gap-[1px]" style="height:1em">
            @for ($__i = 1; $__i <= 5; $__i++)
              <svg style="height:1em;width:1em" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
            @endfor
          </div>
          <span class="rating-text font-normal whitespace-nowrap" style="letter-spacing: 0.31px; color: var(--color-text-subtitle)">({{ $__rv }})</span>
        </div>

        <div class="flex flex-row items-end" style="gap: 5px">
          <p class="current-price font-medium" style="color: #121212">{{ $p['price'] }}</p>
          @if (!empty($p['oldPrice']))
            <p class="original-price font-light" style="text-decoration: line-through; color: var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
          @endif
          @if (!empty($p['discount']))
            <p class="discount-badge font-normal whitespace-nowrap" style="letter-spacing: 0.31px; color: var(--color-secondary)">{{ $p['discount'] }}</p>
          @endif
        </div>

        @if (!empty($p['delivered']))
          <div class="meta-item delivery flex items-center" style="gap: 4px">
            <img src="{{ asset('elora-1/assets/icons/truck-delivery-green.svg') }}" alt="" class="meta-icon shrink-0" />
            <span class="font-medium whitespace-nowrap truncate min-w-0" style="color: var(--color-success)">{{ $p['delivered'] }}</span>
          </div>
      </div>
    </div>
  </a>
</div>
