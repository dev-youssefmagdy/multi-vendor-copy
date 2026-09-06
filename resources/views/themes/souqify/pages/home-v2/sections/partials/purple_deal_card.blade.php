{{-- Figma "Deal Card" (236.19 x 377.33): a 270.36 media block over a 106.97
     white info block. Shared by Trending, Flash Sale and Best Seller, so every
     value is a ratio of the card (container query units) rather than a fixed px
     size - the same markup serves each section at its own width.
     $p keys: id, url, image, name, weight, rating, price, oldPrice, discount,
     discountBg, discountColor, tag, stock --}}
@once
<style>
    .sqv2-deal {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
        /* A definite height: the media block below is a percentage of it, and a
           percentage basis against an indefinite height collapses to content. */
        aspect-ratio: 236.19 / 377.33;
        overflow: hidden;
        background: #FFFFFF;
        border-radius: 7.19362px;
        container-type: inline-size;
        isolation: isolate;
    }
    .sqv2-deal__media {
        position: relative;
        display: block;
        width: 100%;
        /* 270.36 / 377.33 */
        flex: 0 0 71.65%;
        background: #F3F3FE;
        overflow: hidden;
    }
    .sqv2-deal__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    /* Group 22 / Rectangle 5956: the purple tag across the top-left of the
       media - 148.22 x 25.59 of the 236.19 card. */
    .sqv2-deal__tag {
        position: absolute;
        left: 0;
        top: 0;
        z-index: 2;
        display: flex;
        align-items: center;
        width: 62.75%;
        height: 10.83cqw;
        padding: 0 5cqw;
        background: var(--sqv2-deal-accent, #8B03BD);
        /* Square against the card edge and along the top, rounded only at the
           bottom-right. No shadow. */
        border-radius: 0 0 9cqw 0;
    }
    .sqv2-deal__tag-text {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 5cqw;                /* 11.8095 / 236.19 */
        line-height: 1.27;
        letter-spacing: 0.31cqw;
        color: #FFFFFF;
        white-space: nowrap;
    }
    /* heart: 47.24 circle at left 182.36, top 6.99. */
    .sqv2-deal__fav {
        position: absolute;
        top: 2.96cqw;
        right: 2.79cqw;
        z-index: 2;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 20cqw;                   /* 47.24 / 236.19 */
        height: 20cqw;
        padding: 4.21cqw;
        background: #FFFFFF;
        box-shadow: 0px 4.97243px 4.97243px rgba(0, 0, 0, 0.15);
        border: 0;
        border-radius: 50%;
        cursor: pointer;
    }
    .sqv2-deal__fav img { width: 100%; height: 100%; }
    /* BTN: 56.1 square at left 174.19 of 236.19 = 73.75%. */
    .sqv2-deal__cart {
        position: absolute;
        left: 73.75%;
        bottom: 7.7cqw;
        z-index: 2;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 23.75cqw;                /* 56.1 / 236.19 */
        height: 23.75cqw;
        background: #FFFFFF;
        box-shadow: 0px 0px 9.0913px rgba(0, 0, 0, 0.25);
        border-radius: 3.28cqw;         /* 7.73727 */
    }
    .sqv2-deal__cart img { width: 68.4%; height: 68.4%; }
    /* Frame 1984079714: the red ribbon along the bottom of the media. */
    .sqv2-deal__ribbon {
        position: absolute;
        left: 0;
        bottom: 0;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 1.25cqw;
        /* Extra room after the text so the ribbon does not stop flush against
           it - the tail runs on past the label, per the comp. */
        padding: 1.25cqw 8cqw 1.25cqw 2.5cqw;
        max-width: 78%;
        background: linear-gradient(262.6deg, #AC0202 -2.08%, #E30000 44.54%, #9E0000 100%);
        border-radius: 0px 10cqw 0px 0px;
    }
    .sqv2-deal__ribbon img { width: 7.5cqw; height: 7.5cqw; flex: none; }
    .sqv2-deal__ribbon-text {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 5cqw;
        line-height: 1.27;
        color: #FFFFFF;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Container: 106.97 tall, 9.59149px 4.79575px padding, 4.8px gap. */
    .sqv2-deal__info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 2.03cqw;
        width: 100%;
        flex: 1 1 auto;
        min-height: 0;
        padding: 4.06cqw 2.03cqw;
        background: #FFFFFF;
    }
    .sqv2-deal__head {
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        gap: 2.03cqw;
        width: 100%;
    }
    .sqv2-deal__name {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 8.75cqw;             /* 20.6667 / 236.19 */
        line-height: 1.2581;
        color: #191B23;
        text-decoration: none;
        min-width: 0;
        flex: 1 1 auto;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sqv2-deal__weight {
        flex: none;
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 7.5cqw;              /* 17.7143 / 236.19 */
        line-height: 1.2419;
        color: var(--color-primary, #FF4D00);
        white-space: nowrap;
    }
    .sqv2-deal__rating {
        display: flex;
        align-items: center;
        gap: 3cqw;
    }
    .sqv2-deal__stars { height: 5.25cqw; width: 35.98cqw; }
    .sqv2-deal__rating-text {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 7.5cqw;
        line-height: 1.2419;
        letter-spacing: 0.19cqw;
        color: #ADADAD;
        white-space: nowrap;
    }
    /* Frame 1984079750 */
    .sqv2-deal__pricerow {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 3.65cqw;
        width: 100%;
        margin-top: auto;
    }
    .sqv2-deal__prices {
        display: flex;
        align-items: center;
        gap: 2.5cqw;
        min-width: 0;
    }
    .sqv2-deal__price {
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: 10cqw;               /* 23.619 / 236.19 */
        line-height: 1.2701;
        color: var(--sqv2-deal-accent, #8B03BD);
        white-space: nowrap;
    }
    .sqv2-deal__old {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 6.25cqw;             /* 14.7619 / 236.19 */
        line-height: 1.2871;
        text-decoration-line: line-through;
        color: #8F8F8F;
        white-space: nowrap;
    }
    /* Discount: no radius in the comp, and the fill cycles per card. */
    .sqv2-deal__discount {
        display: flex;
        justify-content: center;
        align-items: center;
        flex: none;
        padding: 0 2.5cqw;
        height: 12.79cqw;               /* 30.2 / 236.19 */
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 5.48cqw;             /* 12.9413 / 236.19 */
        line-height: 1.2363;
        letter-spacing: 0.23cqw;
        white-space: nowrap;
    }
</style>
@endonce

<div class="sqv2-deal">
    <a href="{{ $p['url'] }}" class="sqv2-deal__media">
        @if ($p['image'])
            <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="sqv2-deal__img" />
        @endif

        @if (!empty($p['tag']))
            <span class="sqv2-deal__tag"><span class="sqv2-deal__tag-text">{{ $p['tag'] }}</span></span>
        @endif
        @if (!empty($p['stock']))
            <span class="sqv2-deal__ribbon">
                <img src="{{ asset('souqify-1/assets/icons/icon-cart-x.svg') }}" alt="" />
                <span class="sqv2-deal__ribbon-text">{{ $p['stock'] }}</span>
            </span>
        @endif

        <span class="sqv2-deal__cart">
            <img src="{{ asset('souqify-1/assets/icons/icon-cart-add.svg') }}" alt="{{ __('Add to cart') }}" />
        </span>
    </a>

    <button type="button" wire:click.stop="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to favorites') }}" class="sqv2-deal__fav">
        <img src="{{ asset('souqify-1/assets/icons/icon-heart-outline.svg') }}" alt="" />
    </button>

    <div class="sqv2-deal__info">
        <div class="sqv2-deal__head">
            <a href="{{ $p['url'] }}" class="sqv2-deal__name">{{ $p['name'] }}</a>
            @if (!empty($p['weight']))
                <span class="sqv2-deal__weight">{{ $p['weight'] }}</span>
            @endif
        </div>

        <div class="sqv2-deal__rating">
            <img src="{{ asset('souqify-1/assets/icons/icon-star-rating.svg') }}" alt="" class="sqv2-deal__stars" />
            <span class="sqv2-deal__rating-text">{{ $p['rating'] }}</span>
        </div>

        <div class="sqv2-deal__pricerow">
            <div class="sqv2-deal__prices">
                <span class="sqv2-deal__price">{{ $p['price'] }}</span>
                @if (!empty($p['oldPrice']))
                    <span class="sqv2-deal__old">{{ $p['oldPrice'] }}</span>
                @endif
            </div>
            @if (!empty($p['discount']))
                <span class="sqv2-deal__discount" style="background:{{ $p['discountBg'] ?? '#FFB00A' }}; color:{{ $p['discountColor'] ?? '#121212' }}">{{ $p['discount'] }}</span>
            @endif
        </div>
    </div>
</div>
