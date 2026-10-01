{{-- Figma "Deal Card". $p keys: id, url, image, name, rating, price, oldPrice,
     discount, discountBg, discountColor, tag (orange ribbon tag), stock (red
     ribbon text). The styles come from the @once partial so the three sections
     that use this card ship one copy of them. --}}
@include('themes.souqify.pages.home-v5.sections.partials.deal_card_styles')

<div class="sqv5-deal">
    <a href="{{ $p['url'] }}" class="sqv5-deal__media">
        @if ($p['image'])
            <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="sqv5-deal__img" onerror="this.style.display='none'" />
        @endif

        @if (!empty($p['tag']))
            <span class="sqv5-deal__tag"><span class="sqv5-deal__tag-text">{{ $p['tag'] }}</span></span>
        @endif
        @if (!empty($p['stock']))
            <span class="sqv5-deal__ribbon">
                <img src="{{ asset('souqify-4/assets/icons/trending-cartx.svg') }}" alt="" />
                <span class="sqv5-deal__ribbon-text">{{ $p['stock'] }}</span>
            </span>
        @endif

        <span class="sqv5-deal__cart" role="button" aria-label="{{ __('Add to cart') }}"
            @if (!empty($p['outOfStock']))
                aria-disabled="true" onclick="event.preventDefault();event.stopPropagation();" style="opacity:.5;cursor:not-allowed"
            @else
                wire:click="addToCart({{ $p['id'] }})" wire:loading.attr="disabled" wire:target="addToCart({{ $p['id'] }})"
                onclick="event.preventDefault();event.stopPropagation();"
            @endif>
            {{-- The cart glyph carries its colour in the file, so a section that
                 overrides the accent (Best Seller) needs its own asset. --}}
            <img src="{{ asset('souqify-4/assets/icons/' . ($p['cartIcon'] ?? 'trending-cart.svg')) }}" alt="{{ __('Add to cart') }}" />
        </span>
    </a>

    <button type="button" class="sqv5-deal__fav souqify-fav-btn" onclick="souqifyToggleFavorite(this)"
        data-slug="{{ $p['slug'] ?? '' }}" data-fav='{{ $p['favData'] ?? '{}' }}' aria-label="{{ __('Wishlist') }}">
        <img src="{{ asset('souqify-4/assets/icons/trending-heart.svg') }}" alt="" />
    </button>

    <div class="sqv5-deal__info">
        <a href="{{ $p['url'] }}" class="sqv5-deal__name">{{ $p['name'] }}</a>

        @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
        <div class="sqv5-deal__rating">
            <div class="sqv5-deal__stars" style="display:flex;align-items:center;gap:1px">
              @for ($__i = 1; $__i <= 5; $__i++)
                <svg style="height:100%;width:auto" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
              @endfor
            </div>
            <span class="sqv5-deal__rating-text">({{ $__rv }})</span>
        </div>

        <div class="sqv5-deal__pricerow">
            <div class="sqv5-deal__prices">
                <span class="sqv5-deal__price">{{ $p['price'] }}</span>
                @if (!empty($p['oldPrice']))
                    <span class="sqv5-deal__old">{{ $p['oldPrice'] }}</span>
                @endif
            </div>
            @if (!empty($p['discount']))
                <span class="sqv5-deal__discount" style="background:{{ $p['discountBg'] ?? $p['badgeBg'] ?? 'var(--color-accent-yellow)' }}; color:{{ $p['discountColor'] ?? $p['badgeText'] ?? '#121212' }}">{{ $p['discount'] }}</span>
            @endif
        </div>
    </div>
</div>
