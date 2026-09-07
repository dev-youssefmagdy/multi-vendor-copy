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

        <span class="sqv5-deal__cart">
            {{-- The cart glyph carries its colour in the file, so a section that
                 overrides the accent (Best Seller) needs its own asset. --}}
            <img src="{{ asset('souqify-4/assets/icons/' . ($p['cartIcon'] ?? 'trending-cart.svg')) }}" alt="{{ __('Add to cart') }}" />
        </span>
    </a>

    <button type="button" wire:click.stop="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to favorites') }}" class="sqv5-deal__fav">
        <img src="{{ asset('souqify-4/assets/icons/trending-heart.svg') }}" alt="" />
    </button>

    <div class="sqv5-deal__info">
        <a href="{{ $p['url'] }}" class="sqv5-deal__name">{{ $p['name'] }}</a>

        <div class="sqv5-deal__rating">
            <img src="{{ asset('souqify-4/assets/icons/trending-star-rating.svg') }}" alt="" class="sqv5-deal__stars" onerror="this.style.display='none'" />
            <span class="sqv5-deal__rating-text">{{ $p['rating'] }}</span>
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
