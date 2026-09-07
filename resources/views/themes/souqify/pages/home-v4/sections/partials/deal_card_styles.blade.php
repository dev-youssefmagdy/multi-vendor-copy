@once
{{-- Shared styles for the Deal Card used by both Flash Sale and Best Seller.
     Internal sizes are cqw against the card's own width, so the same rules hold at
     any card size. The section supplies position/width/height and the accent colour
     via --sqv4-deal-accent (Flash Sale #8B03BD, Best Seller #5A9B00). --}}
<style>
    .sqv4-deal {
        position: absolute;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 0;
        text-decoration: none;
        container-type: inline-size;
        isolation: isolate;
        filter: drop-shadow(0px 0px 50.0403px rgba(0, 0, 0, 0.12));
    }
    .sqv4-deal__media {
        position: relative;
        width: 100%;
        /* 261.85 / 366.14 of the card's height */
        flex: 0 0 71.52%;
        background: #F3F3FE;
        overflow: hidden;
    }
    .sqv4-deal__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    /* Group 22: 143.55 x 24.78 chip pinned to the card's top-left. */
    .sqv4-deal__badge {
        position: absolute;
        top: 0;
        left: 0;
        z-index: 1;
        display: flex;
        align-items: center;
        width: 62.75cqw;                /* 157.24 / 250.57 */
        height: 10.83cqw;               /* 27.14 / 250.57 */
        padding: 0 5cqw;                /* text starts at 12.53 */
        background: var(--sqv4-deal-accent, #8B03BD);
        /* Rectangle 5956 is the bottom ribbon flipped vertically
           (matrix(1,0,0,-1,0,0)), so its rounded corner lands bottom-right. */
        border-radius: 0px 0px 10cqw 0px;
        box-shadow: 1.8719px -0.935951px 2.80785px rgba(0, 0, 0, 0.25);
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 5cqw;
        line-height: 1.2240;            /* 14 / 11.4378 */
        letter-spacing: 0.714861px;
        color: #FFFFFF;
        white-space: nowrap;
        overflow: hidden;
    }
    /* heart: 45.75 circle, icon 24.08 */
    .sqv4-deal__heart {
        position: absolute;
        top: 2.96cqw;                   /* 7.42 / 250.57 */
        right: 2.79cqw;                 /* 7.00 / 250.57 */
        z-index: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 20cqw;
        height: 20cqw;
        padding: 4.21cqw;
        background: #FFFFFF;
        box-shadow: 0px 4.81591px 4.81591px rgba(0, 0, 0, 0.15);
        border-radius: 60.1988px;
    }
    .sqv4-deal__heart img { width: 100%; height: 100%; object-fit: contain; }
    /* BTN: 54.33 square, radius 7.49371, icon 37.17 */
    .sqv4-deal__cart {
        position: absolute;
        right: 2.5cqw;                  /* 6.27 / 250.57 */
        bottom: 4.63cqw;                /* 11.61 / 250.57 */
        z-index: 2;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 23.75cqw;
        height: 23.75cqw;
        padding: 3.75cqw;               /* icon 40.72 inside the 59.51 button */
        background: #FFFFFF;
        box-shadow: 0px 0px 8.80512px rgba(0, 0, 0, 0.25);
        border-radius: 3.276cqw;
    }
    .sqv4-deal__cart img { width: 100%; height: 100%; object-fit: contain; }
    /* Frame 1984079714: the red ribbon along the bottom of the image. */
    .sqv4-deal__ribbon {
        position: absolute;
        left: 0;
        bottom: 0;
        z-index: 1;
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 1.25cqw;
        width: 78cqw;
        height: 10cqw;
        padding: 1.25cqw 8cqw 1.25cqw 2.5cqw;
        background: linear-gradient(262.6deg, #AC0202 -2.08%, #E30000 44.54%, #9E0000 100%);
        border-radius: 0px 10cqw 0px 0px;
        overflow: hidden;
    }
    .sqv4-deal__ribbon-icon { width: 7.5cqw; height: 7.5cqw; flex: none; }
    .sqv4-deal__ribbon-text {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 5cqw;
        line-height: 1.2240;
        color: #FFFFFF;
        white-space: nowrap;
    }

    /* Container: white info block, 228.76 x 104.11 */
    .sqv4-deal__body {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 4.06cqw 2.03cqw;
        gap: 2.03cqw;
        width: 100%;
        /* 104.11 / 366.14 - the remainder under the media block */
        flex: 1 1 auto;
        justify-content: center;
        min-height: 0;
        background: #FFFFFF;
        overflow: hidden;
    }
    .sqv4-deal__titlerow {
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        gap: 2.03cqw;
        width: 100%;
        min-width: 0;
    }
    .sqv4-deal__name {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 8.749cqw;
        line-height: 1.2490;            /* 25 / 20.0161 */
        color: #191B23;
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sqv4-deal__weight {
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 7.5cqw;
        line-height: 1.2823;            /* 22 / 17.1567 */
        color: #FF4D00;
        flex: none;
    }
    .sqv4-deal__rating {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        gap: 3cqw;
    }
    .sqv4-deal__stars {
        /* 82.31 x 12.01 */
        width: 35.98cqw;
        height: 5.25cqw;
        flex: none;
    }
    .sqv4-deal__ratingtext {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 7.5cqw;
        line-height: 1.2823;
        letter-spacing: 0.429025px;
        color: #ADADAD;
        white-space: nowrap;
    }
    .sqv4-deal__pricerow {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 3.65cqw;
        width: 100%;
    }
    .sqv4-deal__prices {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 2.5cqw;
        min-width: 0;
    }
    .sqv4-deal__price {
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: 10cqw;
        line-height: 1.2678;            /* 29 / 22.8756 */
        color: var(--sqv4-deal-accent, #8B03BD);
        white-space: nowrap;
    }
    .sqv4-deal__oldprice {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 6.25cqw;
        line-height: 1.2590;            /* 18 / 14.2972 */
        text-decoration-line: line-through;
        color: #8F8F8F;
        white-space: nowrap;
    }
    .sqv4-deal__discount {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 0 2.5cqw;
        height: 12.79cqw;               /* 29.25 / 228.76 */
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 5.479cqw;
        line-height: 1.2765;            /* 16 / 12.5339 */
        letter-spacing: 0.522246px;
        white-space: nowrap;
        flex: none;
    }

</style>
@endonce
