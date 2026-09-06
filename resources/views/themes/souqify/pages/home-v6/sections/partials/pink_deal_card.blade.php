{{-- Figma "Deal Card" (289.73 x 472.89): a 338.19 media block over a 134.77
     white info block. Shared by Flash Sale, Trending and Best Seller, so every
     value is a ratio of the card (container query units) rather than a fixed px
     size - the same markup serves each section at its own width.
     $p keys: id, url, image, name, weight, rating, price, oldPrice, discount,
     discountBg, discountColor, tag, stock --}}
@once
<style>
    .sqv6-deal {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
        /* A definite height: the media block below is a percentage of it, and a
           percentage basis against an indefinite height collapses to content. */
        aspect-ratio: 289.73 / 472.89;
        overflow: hidden;
        background: #FFFFFF;
        box-shadow: 0px 0px 22px rgba(0, 0, 0, 0.12);
        container-type: inline-size;
        isolation: isolate;
    }
    .sqv6-deal__media {
        position: relative;
        display: block;
        width: 100%;
        /* 338.19 / 472.89 */
        flex: 0 0 71.51%;
        background: #F3F3FE;
        overflow: hidden;
    }
    .sqv6-deal__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    /* Group 22 / Rectangle 5956: the pink tag across the top-left of the media. */
    .sqv6-deal__tag {
        position: absolute;
        left: 0;
        top: 0;
        z-index: 2;
        display: flex;
        align-items: center;
        /* 185.4 x 32 of the 289.73 card */
        width: 63.99%;
        height: 11.04cqw;
        padding: 0 5cqw;
        background: var(--sqv6-deal-accent, #FD1A8F);
        /* Square against the card edge, rounded on the trailing edge - the
           bottom-right corner rounder than the top-right. */
        border-radius: 0 40px 999px 0;
        box-shadow: 2.20719px -1.1036px 3.31079px rgba(0, 0, 0, 0.25);
    }
    .sqv6-deal__tag-text {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 5.1cqw;              /* 14.7723 / 289.73 */
        line-height: 1.286;
        letter-spacing: 0.32cqw;
        color: #FFFFFF;
        white-space: nowrap;
    }
    /* heart: 59.09 circle at the top-right. */
    .sqv6-deal__fav {
        position: absolute;
        top: 3.02cqw;
        right: 2.9cqw;
        z-index: 2;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 20.39cqw;                /* 59.09 / 289.73 */
        height: 20.39cqw;
        padding: 4.29cqw;
        background: #FFFFFF;
        box-shadow: 0px 6.21993px 6.21993px rgba(0, 0, 0, 0.15);
        border: 0;
        border-radius: 50%;
        cursor: pointer;
    }
    .sqv6-deal__fav img { width: 100%; height: 100%; }
    /* BTN: 70.17 square at left 217.89 of 289.73 = 75.2%. */
    .sqv6-deal__cart {
        position: absolute;
        left: 75.2%;
        bottom: 7.7cqw;
        z-index: 2;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 24.22cqw;                /* 70.17 / 289.73 */
        height: 24.22cqw;
        background: #FFFFFF;
        box-shadow: 0px 0px 11.3722px rgba(0, 0, 0, 0.25);
        border-radius: 3.34cqw;         /* 9.67843 */
    }
    .sqv6-deal__cart img { width: 68.4%; height: 68.4%; }
    /* Frame 1984079714: the red ribbon along the bottom of the media. */
    .sqv6-deal__ribbon {
        position: absolute;
        left: 0;
        bottom: 0;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 1.27cqw;
        padding: 1.27cqw 8cqw 1.27cqw 2.55cqw;
        max-width: 79%;
        background: linear-gradient(262.6deg, #AC0202 -2.08%, #E30000 44.54%, #9E0000 100%);
        border-radius: 0px 10.2cqw 0px 0px;
    }
    .sqv6-deal__ribbon img { width: 7.65cqw; height: 7.65cqw; flex: none; }
    .sqv6-deal__ribbon-text {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 5.1cqw;
        line-height: 1.286;
        color: #FFFFFF;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Container: 134.77 tall, 11.9978px 5.99892px padding, 6px gap. */
    .sqv6-deal__info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 2.07cqw;
        width: 100%;
        flex: 1 1 auto;
        min-height: 0;
        padding: 4.14cqw 2.07cqw;
        background: #FFFFFF;
    }
    .sqv6-deal__head {
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        gap: 2.07cqw;
        width: 100%;
    }
    .sqv6-deal__name {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 8.92cqw;             /* 25.8516 / 289.73 */
        line-height: 1.2765;
        color: #191B23;
        text-decoration: none;
        min-width: 0;
        flex: 1 1 auto;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sqv6-deal__weight {
        flex: none;
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 7.65cqw;             /* 22.1585 / 289.73 */
        line-height: 1.2636;
        color: var(--color-primary, #FF4D00);
        white-space: nowrap;
    }
    .sqv6-deal__rating {
        display: flex;
        align-items: center;
        gap: 3.06cqw;
    }
    .sqv6-deal__stars { height: 5.35cqw; width: 36.69cqw; }
    .sqv6-deal__rating-text {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 7.65cqw;
        line-height: 1.2636;
        letter-spacing: 0.19cqw;
        color: #ADADAD;
        white-space: nowrap;
    }
    /* Frame 1984079750 */
    .sqv6-deal__pricerow {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 3.72cqw;
        width: 100%;
        margin-top: auto;
    }
    .sqv6-deal__prices {
        display: flex;
        align-items: center;
        gap: 2.55cqw;
        min-width: 0;
    }
    .sqv6-deal__price {
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: 10.2cqw;             /* 29.5447 / 289.73 */
        line-height: 1.2523;
        color: var(--sqv6-deal-accent, #FD1A8F);
        white-space: nowrap;
    }
    .sqv6-deal__old {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 6.37cqw;             /* 18.4654 / 289.73 */
        line-height: 1.2456;
        text-decoration-line: line-through;
        color: #8F8F8F;
        white-space: nowrap;
    }
    /* Discount: no radius in the comp, and the fill cycles per card. */
    .sqv6-deal__discount {
        display: flex;
        justify-content: center;
        align-items: center;
        flex: none;
        padding: 0 2.55cqw;
        height: 13.04cqw;               /* 37.77 / 289.73 */
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 5.59cqw;             /* 16.188 / 289.73 */
        line-height: 1.2355;
        letter-spacing: 0.23cqw;
        white-space: nowrap;
    }
</style>
@endonce

<div class="sqv6-deal">
    <a href="{{ $p['url'] }}" class="sqv6-deal__media">
        @if ($p['image'])
            <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="sqv6-deal__img" onerror="this.style.display='none'" />
        @endif

        @if (!empty($p['tag']))
            <span class="sqv6-deal__tag"><span class="sqv6-deal__tag-text">{{ $p['tag'] }}</span></span>
        @endif
        @if (!empty($p['stock']))
            <span class="sqv6-deal__ribbon">
                <img src="{{ asset('souqify-5/assets/icons/cart-x.svg') }}" alt="" />
                <span class="sqv6-deal__ribbon-text">{{ $p['stock'] }}</span>
            </span>
        @endif

        <span class="sqv6-deal__cart">
            <img src="{{ asset('souqify-5/assets/icons/cart-add.svg') }}" alt="{{ __('Add to cart') }}" />
        </span>
    </a>

    <button type="button" wire:click.stop="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to favorites') }}" class="sqv6-deal__fav">
        <img src="{{ asset('souqify-5/assets/icons/heart.svg') }}" alt="" />
    </button>

    <div class="sqv6-deal__info">
        <div class="sqv6-deal__head">
            <a href="{{ $p['url'] }}" class="sqv6-deal__name">{{ $p['name'] }}</a>
            @if (!empty($p['weight']))
                <span class="sqv6-deal__weight">{{ $p['weight'] }}</span>
            @endif
        </div>

        <div class="sqv6-deal__rating">
            <img src="{{ asset('souqify-5/assets/icons/star-rating.svg') }}" alt="" class="sqv6-deal__stars" onerror="this.style.display='none'" />
            <span class="sqv6-deal__rating-text">{{ $p['rating'] }}</span>
        </div>

        <div class="sqv6-deal__pricerow">
            <div class="sqv6-deal__prices">
                <span class="sqv6-deal__price">{{ $p['price'] }}</span>
                @if (!empty($p['oldPrice']))
                    <span class="sqv6-deal__old">{{ $p['oldPrice'] }}</span>
                @endif
            </div>
            @if (!empty($p['discount']))
                <span class="sqv6-deal__discount" style="background:{{ $p['discountBg'] ?? '#FFB00A' }}; color:{{ $p['discountColor'] ?? '#121212' }}">{{ $p['discount'] }}</span>
            @endif
        </div>
    </div>
</div>
