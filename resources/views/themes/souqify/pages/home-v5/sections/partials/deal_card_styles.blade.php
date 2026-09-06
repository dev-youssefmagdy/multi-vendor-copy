@once
{{-- Figma "Deal Card" (226.03 x 361.17): a 258.73 media block over a 102.43 white
     info block. Shared by Trending Now, Top Products and Recommended For You, so
     every value here is a ratio of the card rather than a fixed pixel size.
     Sizes are clamp(min, <value>/1440*100vw, max) off the 1440 canvas. --}}
<style>
    .sqv5-deal {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
        /* A definite height: the media block below is a percentage of it, and a
           percentage basis against an indefinite height collapses to content. */
        aspect-ratio: 226.03 / 361.17;
        border-radius: 6.88422px;
        overflow: hidden;
        background: #FFFFFF;
        box-shadow: var(--shadow-card);
        container-type: inline-size;
        isolation: isolate;
    }
    .sqv5-deal__media {
        position: relative;
        display: block;
        width: 100%;
        /* 258.73 / 361.17 */
        flex: 0 0 71.63%;
        background: #F3F3FE;
        overflow: hidden;
    }
    .sqv5-deal__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    /* heart: 45.21 square at left 174.51 / top 6.69 of the 226.03 card. */
    .sqv5-deal__fav {
        position: absolute;
        top: 2.96cqw;
        right: 2.79cqw;
        z-index: 2;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 20cqw;                   /* 45.21 / 226.03 */
        height: 20cqw;
        padding: 4.21cqw;
        background: #FFFFFF;
        box-shadow: 0px 4.75856px 4.75856px rgba(0, 0, 0, 0.15);
        border: 0;
        border-radius: 50%;
        cursor: pointer;
    }
    .sqv5-deal__fav img { width: 100%; height: 100%; }
    /* BTN: 53.68 square at left 166.7 of 226.03 = 73.75%. */
    .sqv5-deal__cart {
        position: absolute;
        left: 73.75%;
        bottom: 7.9cqw;
        z-index: 2;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 23.75cqw;                /* 53.68 / 226.03 */
        height: 23.75cqw;
        background: #FFFFFF;
        box-shadow: 0px 0px 8.70027px rgba(0, 0, 0, 0.25);
        border-radius: 7.40449px;
    }
    .sqv5-deal__cart img { width: 68.4%; height: 68.4%; }   /* 36.73 / 53.68 */

    /* Frame 1984079714: the red ribbon across the bottom of the media. */
    .sqv5-deal__ribbon {
        position: absolute;
        left: 0;
        bottom: 0;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 1.25cqw;
        padding: 2.8254px 8cqw 2.8254px 5.65079px;
        max-width: 78%;
        background: linear-gradient(262.6deg, #AC0202 -2.08%, #E30000 44.54%, #9E0000 100%);
        border-radius: 0px 22.6032px 0px 0px;
    }
    .sqv5-deal__ribbon img { width: 7.5cqw; height: 7.5cqw; flex: none; }
    .sqv5-deal__ribbon-text {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 5cqw;                /* 11.3016 / 226.03 */
        line-height: 1.2387;
        color: #FFFFFF;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    /* Group 22 / Rectangle 5956: the orange tag at the top-left of the media,
       141.84 x 24.48 with the corner cut off its trailing edge. */
    .sqv5-deal__tag {
        position: absolute;
        left: 0;
        top: 0;
        z-index: 2;
        display: flex;
        align-items: center;
        /* Rectangle 5956: 141.84 x 24.48, square against the card edge and
           fully rounded on its trailing edge. */
        width: 62.75%;                  /* 141.84 / 226.03 */
        height: 10.83cqw;               /* 24.48 / 226.03 */
        padding: 0 5cqw;                /* Mobile labes sits at left 11.3 */
        /* Each section may set --sqv5-deal-accent; the token is the default. */
        background: var(--sqv5-deal-accent, var(--color-primary));
        border-radius: 0 290px 999px 0;
    }
    .sqv5-deal__tag-text {
        position: relative;
        z-index: 1;
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 5cqw;
        line-height: 1.2387;
        letter-spacing: 0.706349px;
        color: #FFFFFF;
        white-space: nowrap;
    }

    /* Container: 102.43 tall, 9.17895px 4.58948px padding, 4.59px gap. */
    .sqv5-deal__info {
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
    .sqv5-deal__name {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 8.75cqw;             /* 19.7778 / 226.03 */
        line-height: 1.2641;
        color: #191B23;
        text-decoration: none;
        width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sqv5-deal__rating {
        display: flex;
        align-items: center;
        gap: 3cqw;
    }
    .sqv5-deal__stars { height: 5.25cqw; width: 36cqw; }
    .sqv5-deal__rating-text {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 7.5cqw;              /* 16.9524 / 226.03 */
        line-height: 1.2388;
        letter-spacing: 0.423916px;
        color: #ADADAD;
        white-space: nowrap;
    }
    .sqv5-deal__pricerow {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 3.65cqw;
        width: 100%;
        margin-top: auto;
    }
    .sqv5-deal__prices {
        display: flex;
        align-items: center;
        gap: 2.5cqw;
        min-width: 0;
    }
    .sqv5-deal__price {
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: 10cqw;               /* 22.6032 / 226.03 */
        line-height: 1.2388;
        color: var(--sqv5-deal-accent, var(--color-primary));
        white-space: nowrap;
    }
    .sqv5-deal__old {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 6.25cqw;             /* 14.127 / 226.03 */
        line-height: 1.2742;
        text-decoration-line: line-through;
        color: #8F8F8F;
        white-space: nowrap;
    }
    /* Discount: no radius in the comp, and the fill cycles per card. */
    .sqv5-deal__discount {
        display: flex;
        justify-content: center;
        align-items: center;
        flex: none;
        padding: 0 2.5cqw;
        height: 12.79cqw;               /* 28.9 / 226.03 */
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 5.48cqw;             /* 12.3847 / 226.03 */
        line-height: 1.2919;
        letter-spacing: 0.516028px;
        white-space: nowrap;
    }
</style>
@endonce
