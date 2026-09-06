{{-- Expects $p: url, image, name, weight, desc, badge, badgeBg, badgeText, progress, ordered, rating, price, oldPrice, discount, delivered --}}
<div class="swiper-slide product-card">
  <a href="{{ $p['url'] ?? '#' }}" class="flex flex-row w-full h-full overflow-hidden">
    {{-- Media --}}
    <div class="card-media">
      <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="product-image" />

      <div class="top-overlay">
        <button
          type="button"
          aria-label="Add to favorites"
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
        <div class="cart-btn flex items-center justify-center">
          <img src="{{ asset('elora-1/assets/icons/cart-plus.svg') }}" alt="Add to cart" class="icon" />
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
            <div class="absolute top-0 left-0 h-full" style="background: var(--color-accent-purple); width: {{ $p['progress'] }}%; border-radius: 24px"></div>
          </div>
          @if (!empty($p['ordered']))
            <p class="progress-text font-normal" style="letter-spacing: 0.31px; color: var(--color-accent-purple)">{{ $p['ordered'] }}</p>
          @endif
        </div>
      @endif

      <div class="flex flex-col items-start" style="gap: 2.5px">
        <div class="flex flex-row items-center" style="gap: 5px">
          <img src="{{ asset('elora-1/assets/icons/star-rating.svg') }}" alt="" class="stars" style="height: 1em; width: auto" />
          @if (!empty($p['rating']))
            <span class="rating-text font-normal whitespace-nowrap" style="letter-spacing: 0.31px; color: var(--color-text-subtitle)">{{ $p['rating'] }}</span>
          @endif
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
        @endif
      </div>
    </div>
  </a>
</div>
