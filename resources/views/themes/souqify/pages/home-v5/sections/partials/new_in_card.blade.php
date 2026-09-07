{{-- Figma "Mobile Card" (554 x 145.31): a 200.16 media block beside the info
     column. Every size is a ratio of the card (container query units) so the
     same markup survives the carousel scaling the column down.
     $p keys: id, url, image, name, weight, subtitle, rating, price, oldPrice,
     discount, sold, delivery, stock --}}
@once
<style>
    .sqv5-new {
        position: relative;
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        gap: 0.875cqw;                  /* 4.85 / 554 */
        width: 100%;
        /* A definite height: the media block is a percentage of the card. */
        aspect-ratio: 554 / 145.31;
        background: #FDFDFD;
        /* No radius here: the card clips its children, so any rounding on the
           shell rounds the photo's corners too. */
        border-radius: 0;
        overflow: hidden;
        container-type: inline-size;
        isolation: isolate;
    }
    /* Frame 1984079719: the photo, with the sale badge and heart over it. */
    .sqv5-new__media {
        position: relative;
        display: block;
        flex: 0 0 36.13%;               /* 200.16 / 554 */
        align-self: stretch;
        background: #F3F3FE;
        overflow: hidden;
    }
    .sqv5-new__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    /* sale: 63.68 x 24.92, square against the card edge. */
    .sqv5-new__sale {
        position: absolute;
        left: 0;
        bottom: 0;
        z-index: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 0 0.964cqw;
        height: 4.499cqw;               /* 24.92 / 554 */
        background: var(--color-accent-yellow, #FFD428);
        border-radius: 0 1.285cqw;      /* 7.11967 */
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 2.249cqw;            /* 12.4594 */
        line-height: 1.284;
        letter-spacing: 0.08cqw;
        color: #242424;
        white-space: nowrap;
    }
    /* heart: 32.49 circle at the top-left of the media. */
    .sqv5-new__fav {
        position: absolute;
        top: 1.1cqw;
        left: 1.1cqw;
        z-index: 2;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 5.865cqw;                /* 32.49 / 554 */
        height: 5.865cqw;
        padding: 1.466cqw;
        background: #FFFFFF;
        box-shadow: 0px 4.06097px 4.06097px rgba(0, 0, 0, 0.15);
        border: 0;
        border-radius: 50%;
        cursor: pointer;
    }
    .sqv5-new__fav img { width: 100%; height: 100%; }
    /* cart: 57.87 x 45.69, radius 16.2439, at the bottom-right of the media. */
    .sqv5-new__cart {
        position: absolute;
        right: 1.1cqw;
        bottom: 0.916cqw;
        z-index: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 10.446cqw;               /* 57.87 / 554 */
        height: 8.247cqw;               /* 45.69 / 554 */
        background: #FDFDFD;
        border-radius: 2.932cqw;        /* 16.2439 */
    }
    .sqv5-new__cart img { width: 42.1%; height: 53.3%; }   /* 24.37 glyph */

    /* Frame 1984079522: 6.46064 padding, 6.46 gap. */
    .sqv5-new__info {
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
    .sqv5-new__head {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 0.367cqw;
        width: 100%;
    }
    .sqv5-new__name {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 3.498cqw;            /* 19.3819 / 554 */
        line-height: 1.238;
        letter-spacing: 0.0916cqw;
        color: #121212;
        text-decoration: none;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sqv5-new__weight {
        flex: none;
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 2.915cqw;            /* 16.1516 / 554 */
        line-height: 1.548;
        letter-spacing: 0.0916cqw;
        color: var(--color-primary);
        white-space: nowrap;
    }
    .sqv5-new__sub {
        width: 100%;
        margin: 0;
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 2.915cqw;
        line-height: 1.238;
        letter-spacing: 0.0916cqw;
        color: #ADADAD;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sqv5-new__rating {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 1.466cqw;
    }
    .sqv5-new__stars { width: 12.552cqw; height: 1.832cqw; }   /* 69.54 x 10.15 */
    .sqv5-new__rating-text {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 2.333cqw;            /* 12.9213 / 554 */
        line-height: 1.238;
        letter-spacing: 0.0916cqw;
        color: #ADADAD;
        white-space: nowrap;
    }
    .sqv5-new__pricerow {
        display: flex;
        flex-direction: row;
        align-items: flex-end;
        gap: 1.466cqw;
    }
    .sqv5-new__price {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 3.498cqw;
        line-height: 1.238;
        color: var(--color-primary);
        white-space: nowrap;
    }
    .sqv5-new__old {
        font-family: 'Outfit', sans-serif;
        font-weight: 300;
        font-size: 2.566cqw;            /* 14.2134 / 554 */
        line-height: 1.266;
        text-decoration-line: line-through;
        color: #ADADAD;
        white-space: nowrap;
    }
    .sqv5-new__off {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 2.333cqw;
        line-height: 1.238;
        letter-spacing: 0.0916cqw;
        color: #FF522C;
        white-space: nowrap;
    }
    /* Frame 1984080009: the two green fulfilment rows. */
    .sqv5-new__meta {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.733cqw;
    }
    .sqv5-new__metarow {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 1.166cqw;
    }
    .sqv5-new__metarow img { width: 3.498cqw; height: 3.498cqw; flex: none; }
    .sqv5-new__metarow span {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 2.333cqw;
        line-height: 1.238;
        color: #2AAF2F;
        white-space: nowrap;
    }
</style>
@endonce

<div class="sqv5-new">
    <a href="{{ $p['url'] }}" class="sqv5-new__media">
        @if ($p['image'])
            <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="sqv5-new__img" onerror="this.style.display='none'" />
        @endif
        @if (!empty($p['sold']))
            <span class="sqv5-new__sale">{{ $p['sold'] }}</span>
        @endif
        <span class="sqv5-new__cart">
            <img src="{{ asset('souqify-4/assets/icons/newin-cart.svg') }}" alt="{{ __('Add to cart') }}" />
        </span>
    </a>

    <button type="button" wire:click.stop="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to favorites') }}" class="sqv5-new__fav">
        <img src="{{ asset('souqify-4/assets/icons/newin-heart.svg') }}" alt="" />
    </button>

    <div class="sqv5-new__info">
        <div class="sqv5-new__head">
            <a href="{{ $p['url'] }}" class="sqv5-new__name">{{ $p['name'] }}</a>
            @if (!empty($p['weight']))
                <span class="sqv5-new__weight">{{ $p['weight'] }}</span>
            @endif
        </div>
        @if (!empty($p['subtitle']))
            <p class="sqv5-new__sub">{{ $p['subtitle'] }}</p>
        @endif

        <div class="sqv5-new__rating">
            <img src="{{ asset('souqify-4/assets/icons/newin-star-rating.svg') }}" alt="" class="sqv5-new__stars" onerror="this.style.display='none'" />
            <span class="sqv5-new__rating-text">{{ $p['rating'] }}</span>
        </div>

        <div class="sqv5-new__pricerow">
            <span class="sqv5-new__price">{{ $p['price'] }}</span>
            @if (!empty($p['oldPrice']))
                <span class="sqv5-new__old">{{ $p['oldPrice'] }}</span>
            @endif
            @if (!empty($p['discount']))
                <span class="sqv5-new__off">{{ $p['discount'] }}</span>
            @endif
        </div>

        <div class="sqv5-new__meta">
            @if (!empty($p['delivery']))
                <span class="sqv5-new__metarow">
                    <img src="{{ asset('souqify-4/assets/icons/newin-truck.svg') }}" alt="" />
                    <span>{{ $p['delivery'] }}</span>
                </span>
            @endif
        </div>
    </div>
</div>
