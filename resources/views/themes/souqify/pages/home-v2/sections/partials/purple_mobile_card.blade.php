{{-- Figma "Mobile Card" (411.94 x 108.05): a 148.83 media block beside the info
     column. Every size is a ratio of the card (container query units) so the
     same markup survives the carousel scaling the column down.
     $p keys: id, url, image, name, weight, subtitle, rating, price, oldPrice,
     discount, sold, delivery, stock --}}
@once
<style>
    .sqv2-mob {
        position: relative;
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        gap: 0.874cqw;                  /* 3.6 / 411.94 */
        width: 100%;
        /* A definite height: the media block is a percentage of the card. */
        aspect-ratio: 411.94 / 108.05;
        background: #FDFDFD;
        border-radius: 7.20597px;
        overflow: hidden;
        container-type: inline-size;
        isolation: isolate;
    }
    /* Frame 1984079719: the photo, with the sale badge, heart and cart over it. */
    .sqv2-mob__media {
        position: relative;
        display: block;
        flex: 0 0 36.13%;               /* 148.83 / 411.94 */
        align-self: stretch;
        background: var(--color-page-bg, #F3F3FE);
        overflow: hidden;
    }
    .sqv2-mob__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    /* sale: 46.94 x 18.53, top-right of the photo - the heart holds the
       top-left corner, so the two cannot share it. */
    .sqv2-mob__sale {
        position: absolute;
        right: 0;
        top: 0;
        z-index: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 0 0.964cqw;
        height: 4.499cqw;               /* 18.53 / 411.94 */
        background: var(--color-accent-yellow, #FFD428);
        border-radius: 1.285cqw 0;      /* 5.29402, mirrored for the right edge */
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 2.249cqw;            /* 9.26453 */
        line-height: 1.295;
        letter-spacing: 0.08cqw;
        color: #242424;
        white-space: nowrap;
    }
    /* heart: 24.16 circle at the top-left of the media. */
    .sqv2-mob__fav {
        position: absolute;
        top: 0.92cqw;
        left: 0.92cqw;
        z-index: 2;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 5.865cqw;                /* 24.16 / 411.94 */
        height: 5.865cqw;
        padding: 1.466cqw;
        background: #FFFFFF;
        box-shadow: 0px 3.01964px 3.01964px rgba(0, 0, 0, 0.15);
        border: 0;
        border-radius: 50%;
        cursor: pointer;
    }
    .sqv2-mob__fav img { width: 100%; height: 100%; }
    /* cart: 43.03 x 33.97, radius 12.0786, bottom-right of the media. */
    .sqv2-mob__cart {
        position: absolute;
        right: 0.92cqw;
        bottom: 0.92cqw;
        z-index: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 10.446cqw;               /* 43.03 / 411.94 */
        height: 8.246cqw;               /* 33.97 / 411.94 */
        background: #FDFDFD;
        border-radius: 2.932cqw;        /* 12.0786 */
    }
    .sqv2-mob__cart img { width: 42.1%; height: 53.3%; }

    /* Frame 1984079522: 4.80398px padding, 4.8px gap. */
    .sqv2-mob__info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        gap: 1.166cqw;
        flex: 1 1 auto;
        min-width: 0;
        align-self: stretch;
        padding: 1.166cqw;
    }
    .sqv2-mob__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 0.367cqw;
        width: 100%;
    }
    .sqv2-mob__name {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 3.499cqw;            /* 14.4119 / 411.94 */
        line-height: 1.2490;
        letter-spacing: 0.09cqw;
        color: #121212;
        text-decoration: none;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sqv2-mob__weight {
        flex: none;
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 2.916cqw;            /* 12.0099 / 411.94 */
        line-height: 1.5820;
        letter-spacing: 0.09cqw;
        color: #132092;
        white-space: nowrap;
    }
    .sqv2-mob__sub {
        width: 100%;
        margin: 0;
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 2.916cqw;
        line-height: 1.2490;
        letter-spacing: 0.09cqw;
        color: #ADADAD;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sqv2-mob__rating {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 1.466cqw;
    }
    .sqv2-mob__stars { width: 12.553cqw; height: 1.833cqw; }   /* 51.71 x 7.55 */
    .sqv2-mob__rating-text {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 2.333cqw;            /* 9.60795 / 411.94 */
        line-height: 1.2490;
        letter-spacing: 0.09cqw;
        color: #ADADAD;
        white-space: nowrap;
    }
    .sqv2-mob__pricerow {
        display: flex;
        flex-direction: row;
        align-items: flex-end;
        gap: 1.466cqw;
    }
    .sqv2-mob__price {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 3.499cqw;
        line-height: 1.2490;
        color: var(--color-brand-purple, #8B03BD);
        white-space: nowrap;
    }
    .sqv2-mob__old {
        font-family: 'Outfit', sans-serif;
        font-weight: 300;
        font-size: 2.566cqw;            /* 10.5688 / 411.94 */
        line-height: 1.2301;
        text-decoration-line: line-through;
        color: #ADADAD;
        white-space: nowrap;
    }
    .sqv2-mob__off {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 2.333cqw;
        line-height: 1.2490;
        letter-spacing: 0.09cqw;
        color: #FF522C;
        white-space: nowrap;
    }
    /* Frame 1984080009: the two green fulfilment rows. */
    .sqv2-mob__meta {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.733cqw;
    }
    .sqv2-mob__metarow {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 1.166cqw;
    }
    .sqv2-mob__metarow img { width: 3.499cqw; height: 3.499cqw; flex: none; }
    .sqv2-mob__metarow span {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 2.333cqw;
        line-height: 1.2490;
        color: #2AAF2F;
        white-space: nowrap;
    }

    /* RTL: mirror the corner-pinned badge/heart/cart positions. */
    [dir="rtl"] .sqv2-mob__sale {
        right: auto;
        left: 0;
    }
    [dir="rtl"] .sqv2-mob__fav {
        left: auto;
        right: 0.92cqw;
    }
    [dir="rtl"] .sqv2-mob__cart {
        right: auto;
        left: 0.92cqw;
    }
</style>
@endonce

<div class="sqv2-mob">
    <a href="{{ $p['url'] }}" class="sqv2-mob__media">
        @if ($p['image'])
            <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="sqv2-mob__img" />
        @endif
        @if (!empty($p['sold']))
            <span class="sqv2-mob__sale">{{ $p['sold'] }}</span>
        @endif
        <span class="sqv2-mob__cart" role="button" tabindex="0" aria-label="{{ __('Add to cart') }}"
            wire:click.stop.prevent="addToCart({{ $p['id'] }})">
            <img src="{{ asset('souqify-1/assets/icons/' . ($cartIcon ?? 'icon-cart-navy.svg')) }}" alt="{{ __('Add to cart') }}" />
        </span>
    </a>

    @if (!empty($p['slug']) && !empty($p['favData']))
        <button type="button" onclick="souqifyToggleFavorite(this)" data-slug="{{ $p['slug'] }}" data-fav='{{ $p['favData'] }}' aria-label="{{ __('Add to favorites') }}" class="sqv2-mob__fav">
            <img class="fav-icon" src="{{ asset('souqify-1/assets/icons/icon-heart-outline.svg') }}" alt="" />
        </button>
    @else
        <button type="button" wire:click.stop="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to favorites') }}" class="sqv2-mob__fav">
            <img src="{{ asset('souqify-1/assets/icons/icon-heart-outline.svg') }}" alt="" />
        </button>
    @endif

    <div class="sqv2-mob__info">
        <div class="sqv2-mob__head">
            <a href="{{ $p['url'] }}" class="sqv2-mob__name">{{ $p['name'] }}</a>
            @if (!empty($p['weight']))
                <span class="sqv2-mob__weight">{{ $p['weight'] }}</span>
            @endif
        </div>
        @if (!empty($p['subtitle']))
            <p class="sqv2-mob__sub">{{ $p['subtitle'] }}</p>
        @endif

        @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
        <div class="sqv2-mob__rating">
            <div class="sqv2-mob__stars" style="display:flex;align-items:center;gap:1px">
              @for ($__i = 1; $__i <= 5; $__i++)
                <svg style="height:100%;width:auto" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
              @endfor
            </div>
            <span class="sqv2-mob__rating-text">({{ $__rv }})</span>
        </div>

        <div class="sqv2-mob__pricerow">
            <span class="sqv2-mob__price">{{ $p['price'] }}</span>
            @if (!empty($p['oldPrice']))
                <span class="sqv2-mob__old">{{ $p['oldPrice'] }}</span>
            @endif
            @if (!empty($p['discount']))
                <span class="sqv2-mob__off">{{ $p['discount'] }}</span>
            @endif
        </div>

        <div class="sqv2-mob__meta">
            @if (!empty($p['delivery']))
                <span class="sqv2-mob__metarow">
                    <img src="{{ asset('souqify-1/assets/icons/icon-truck-mini-green.svg') }}" alt="" />
                    <span>{{ $p['delivery'] }}</span>
                </span>
            @endif
        </div>
    </div>
</div>
