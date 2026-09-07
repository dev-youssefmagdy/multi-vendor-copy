{{-- Figma "Mobile Card" (495.09 x 156.13): a 215.06 media block beside the info
     column. Every size is a ratio of the card (container query units) so the
     same markup survives the carousel scaling the column down.
     $p keys: id, url, image, name, weight, subtitle, rating, price, oldPrice,
     discount, sold, delivery, stock --}}
@once
<style>
    .sqv6-mob {
        position: relative;
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        gap: 1.052cqw;                  /* 5.21 / 495.09 */
        width: 100%;
        /* A definite height: the media block is a percentage of the card. */
        aspect-ratio: 495.09 / 156.13;
        background: #FDFDFD;
        /* 10.4122 on the 495.09 card - a small, fixed corner rather than one
           that grows with the card. */
        border-radius: 6px;
        overflow: hidden;
        container-type: inline-size;
        isolation: isolate;
    }
    /* Frame 1984079719: the photo, with the sale badge, heart and cart over it. */
    .sqv6-mob__media {
        position: relative;
        display: block;
        flex: 0 0 43.44%;               /* 215.06 / 495.09 */
        align-self: stretch;
        background: #F3F3FE;
        overflow: hidden;
    }
    .sqv6-mob__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    /* sale: 68.47 x 26.77, at the top-right of the photo - the heart holds the
       top-left corner, so the two cannot share it. */
    .sqv6-mob__sale {
        position: absolute;
        right: 0;
        top: 0;
        z-index: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 0 1.159cqw;
        height: 5.407cqw;               /* 26.77 / 495.09 */
        background: var(--color-accent-yellow, #FFD428);
        border-radius: 1.545cqw 0;      /* 7.64954, mirrored for the right edge */
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 2.704cqw;            /* 13.3867 */
        line-height: 1.27;
        letter-spacing: 0.097cqw;
        color: #242424;
        white-space: nowrap;
    }
    /* heart: 34.91 circle at the top-left of the media. */
    .sqv6-mob__fav {
        position: absolute;
        top: 1.1cqw;
        left: 1.1cqw;
        z-index: 2;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 7.052cqw;                /* 34.91 / 495.09 */
        height: 7.052cqw;
        padding: 1.763cqw;
        background: #FFFFFF;
        box-shadow: 0px 4.3632px 4.3632px rgba(0, 0, 0, 0.15);
        border: 0;
        border-radius: 50%;
        cursor: pointer;
    }
    .sqv6-mob__fav img { width: 100%; height: 100%; }
    /* cart: 62.18 x 49.09, radius 17.4528, at the bottom-right of the media. */
    .sqv6-mob__cart {
        position: absolute;
        right: 1.1cqw;
        bottom: 1.1cqw;
        z-index: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 12.559cqw;               /* 62.18 / 495.09 */
        height: 9.916cqw;               /* 49.09 / 495.09 */
        background: #FDFDFD;
        border-radius: 3.525cqw;        /* 17.4528 */
    }
    .sqv6-mob__cart img { width: 42.1%; height: 53.3%; }   /* 26.18 glyph */

    /* Frame 1984079522: 6.94146px padding, 6.94px gap. */
    .sqv6-mob__info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        gap: 1.402cqw;
        flex: 1 1 auto;
        min-width: 0;
        align-self: stretch;
        padding: 1.402cqw;
    }
    .sqv6-mob__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 0.44cqw;
        width: 100%;
    }
    .sqv6-mob__name {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 4.206cqw;            /* 20.8244 / 495.09 */
        line-height: 1.2485;
        letter-spacing: 0.11cqw;
        color: #121212;
        text-decoration: none;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sqv6-mob__weight {
        flex: none;
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 3.505cqw;            /* 17.3536 / 495.09 */
        line-height: 1.5559;
        letter-spacing: 0.11cqw;
        color: var(--color-brand-pink, #FF1A90);
        white-space: nowrap;
    }
    .sqv6-mob__sub {
        width: 100%;
        margin: 0;
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 3.505cqw;
        line-height: 1.2677;
        letter-spacing: 0.11cqw;
        color: #ADADAD;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sqv6-mob__rating {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 1.763cqw;
    }
    .sqv6-mob__stars { width: 15.09cqw; height: 2.202cqw; }   /* 74.71 x 10.9 */
    .sqv6-mob__rating-text {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 2.804cqw;            /* 13.8829 / 495.09 */
        line-height: 1.2245;
        letter-spacing: 0.11cqw;
        color: #ADADAD;
        white-space: nowrap;
    }
    .sqv6-mob__pricerow {
        display: flex;
        flex-direction: row;
        align-items: flex-end;
        gap: 1.763cqw;
    }
    .sqv6-mob__price {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 4.206cqw;
        line-height: 1.2485;
        color: var(--color-brand-pink, #FF1A90);
        white-space: nowrap;
    }
    .sqv6-mob__old {
        font-family: 'Outfit', sans-serif;
        font-weight: 300;
        font-size: 3.084cqw;            /* 15.2712 / 495.09 */
        line-height: 1.2442;
        text-decoration-line: line-through;
        color: #ADADAD;
        white-space: nowrap;
    }
    .sqv6-mob__off {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 2.804cqw;
        line-height: 1.2245;
        letter-spacing: 0.11cqw;
        color: #FF522C;
        white-space: nowrap;
    }
    /* Frame 1984080009: the two green fulfilment rows. */
    .sqv6-mob__meta {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.881cqw;
    }
    .sqv6-mob__metarow {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 1.402cqw;
    }
    .sqv6-mob__metarow img { width: 4.206cqw; height: 4.206cqw; flex: none; }
    .sqv6-mob__metarow span {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 2.804cqw;
        line-height: 1.2245;
        color: #2AAF2F;
        white-space: nowrap;
    }
</style>
@endonce

<div class="sqv6-mob">
    <a href="{{ $p['url'] }}" class="sqv6-mob__media">
        @if ($p['image'])
            <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="sqv6-mob__img" onerror="this.style.display='none'" />
        @endif
        @if (!empty($p['sold']))
            <span class="sqv6-mob__sale">{{ $p['sold'] }}</span>
        @endif
        <span class="sqv6-mob__cart">
            <img src="{{ asset('souqify-5/assets/icons/cart-add.svg') }}" alt="{{ __('Add to cart') }}" />
        </span>
    </a>

    <button type="button" wire:click.stop="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to favorites') }}" class="sqv6-mob__fav">
        <img src="{{ asset('souqify-5/assets/icons/heart.svg') }}" alt="" />
    </button>

    <div class="sqv6-mob__info">
        <div class="sqv6-mob__head">
            <a href="{{ $p['url'] }}" class="sqv6-mob__name">{{ $p['name'] }}</a>
            @if (!empty($p['weight']))
                <span class="sqv6-mob__weight">{{ $p['weight'] }}</span>
            @endif
        </div>
        @if (!empty($p['subtitle']))
            <p class="sqv6-mob__sub">{{ $p['subtitle'] }}</p>
        @endif

        <div class="sqv6-mob__rating">
            <img src="{{ asset('souqify-5/assets/icons/star-rating.svg') }}" alt="" class="sqv6-mob__stars" onerror="this.style.display='none'" />
            <span class="sqv6-mob__rating-text">{{ $p['rating'] }}</span>
        </div>

        <div class="sqv6-mob__pricerow">
            <span class="sqv6-mob__price">{{ $p['price'] }}</span>
            @if (!empty($p['oldPrice']))
                <span class="sqv6-mob__old">{{ $p['oldPrice'] }}</span>
            @endif
            @if (!empty($p['discount']))
                <span class="sqv6-mob__off">{{ $p['discount'] }}</span>
            @endif
        </div>

        <div class="sqv6-mob__meta">
            @if (!empty($p['delivery']))
                <span class="sqv6-mob__metarow">
                    <img src="{{ asset('souqify-5/assets/icons/truck-delivery-green.svg') }}" alt="" />
                    <span>{{ $p['delivery'] }}</span>
                </span>
            @endif
        </div>
    </div>
</div>
