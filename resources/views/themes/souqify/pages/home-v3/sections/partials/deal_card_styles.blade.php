@once
{{-- Shared styles for the v3 "Deal Card" (Figma 319.97x511.14). Internal sizes are
     cqw against the card's own width, so the same rules hold at any card size. --}}
<style>
    .sqv3-deal {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 0;
        width: 100%;
        /* Both sections use the same card proportions - Flash Sale 319.97x511.14
           and Best Seller 269.05x430.15 both come out at ~0.626 - so the ratio
           gives the card a definite height. Without it the media block's
           percentage flex-basis resolves against an indefinite height and
           collapses. */
        aspect-ratio: 269.05 / 430.15;
        text-decoration: none;
        container-type: inline-size;
        isolation: isolate;
    }
    .sqv3-deal__media {
        position: relative;
        width: 100%;
        /* 307.97 / 430.15 of the card's height */
        flex: 0 0 71.60%;
        background: #F3F3FE;
        overflow: hidden;
    }
    .sqv3-deal__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    /* Group 22: 200.79 x 34.66 chip pinned to the card's top-left. Rectangle 5956 is
       the bottom ribbon flipped vertically (matrix(1,0,0,-1,0,0)), so its rounded
       corner lands bottom-right. */
    .sqv3-deal__badge {
        position: absolute;
        top: 0;
        left: 0;
        z-index: 1;
        display: flex;
        align-items: center;
        width: 62.75cqw;                /* 200.79 / 319.97 */
        height: 10.83cqw;               /* 34.66 / 319.97 */
        padding: 0 5cqw;                /* text starts at 16 */
        background: var(--sqv3-deal-accent, #199387);
        border-radius: 0px 0px 10cqw 0px;
        box-shadow: 2.39037px -1.19519px 3.58556px rgba(0, 0, 0, 0.25);
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 5cqw;                /* 15.9983 */
        line-height: 1.2501;            /* 20 / 15.9983 */
        letter-spacing: 0.999897px;
        color: #FFFFFF;
        white-space: nowrap;
        overflow: hidden;
    }
    /* heart: 63.99 circle at left 247.04 / top 9.47, icon 33.68 */
    .sqv3-deal__heart {
        position: absolute;
        top: 2.96cqw;
        right: 2.79cqw;
        z-index: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 20cqw;
        height: 20cqw;
        padding: 4.21cqw;
        background: #FFFFFF;
        box-shadow: 0px 6.73615px 6.73615px rgba(0, 0, 0, 0.15);
        border-radius: 84.2018px;
        border: 0;
        cursor: pointer;
    }
    .sqv3-deal__heart img { width: 100%; height: 100%; object-fit: contain; }
    /* BTN: 75.99 square, radius 10.4817, icon 51.99 */
    .sqv3-deal__cart {
        position: absolute;
        right: 2.5cqw;
        bottom: 4.63cqw;
        z-index: 2;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 23.75cqw;
        height: 23.75cqw;
        padding: 3.75cqw;
        background: #FFFFFF;
        box-shadow: 0px 0px 12.316px rgba(0, 0, 0, 0.25);
        border-radius: 3.276cqw;
        border: 0;
        cursor: pointer;
    }
    .sqv3-deal__cart img { width: 100%; height: 100%; object-fit: contain; }
    /* Frame 1984079714: the red ribbon along the bottom of the image, 223.98 x 32. */
    .sqv3-deal__ribbon {
        position: absolute;
        left: 0;
        bottom: 0;
        z-index: 1;
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 1.25cqw;
        width: 73cqw;
        height: 10cqw;
        padding: 1.25cqw 4.5cqw 1.25cqw 2.5cqw;
        background: linear-gradient(262.6deg, #AC0202 -2.08%, #E30000 44.54%, #9E0000 100%);
        border-radius: 0px 10cqw 0px 0px;
        overflow: hidden;
    }
    .sqv3-deal__ribbon-icon { width: 7.5cqw; height: 7.5cqw; flex: none; }
    .sqv3-deal__ribbon-text {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 5cqw;                /* 15.9983 */
        line-height: 1.2501;
        color: #FFFFFF;
        white-space: nowrap;
    }

    /* Container: white info block, 319.97 x 144.89 */
    .sqv3-deal__body {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        padding: 4.06cqw 2.03cqw;       /* 12.9936 / 6.49679 */
        gap: 2.03cqw;
        width: 100%;
        flex: 1 1 auto;
        min-height: 0;
        background: #FFFFFF;
        overflow: hidden;
    }
    .sqv3-deal__titlerow {
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        gap: 2.03cqw;
        width: 100%;
        min-width: 0;
    }
    .sqv3-deal__name {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 8.749cqw;            /* 27.9971 */
        line-height: 1.2501;            /* 35 / 27.9971 */
        color: #191B23;
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sqv3-deal__weight {
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 7.5cqw;              /* 23.9975 */
        line-height: 1.2501;            /* 30 / 23.9975 */
        color: #FF4D00;
        flex: none;
    }
    .sqv3-deal__rating {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        gap: 3cqw;
    }
    .sqv3-deal__stars {
        /* 115.13 x 16.8 */
        width: 35.98cqw;
        height: 5.25cqw;
        flex: none;
    }
    .sqv3-deal__ratingtext {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 7.5cqw;
        line-height: 1.2501;
        letter-spacing: 0.600089px;
        color: #ADADAD;
        white-space: nowrap;
    }
    .sqv3-deal__pricerow {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 3.65cqw;
        width: 100%;
    }
    .sqv3-deal__prices {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 2.5cqw;
        min-width: 0;
    }
    .sqv3-deal__price {
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: 10cqw;               /* 31.9967 */
        line-height: 1.2501;            /* 40 / 31.9967 */
        color: var(--sqv3-deal-accent, #199387);
        white-space: nowrap;
    }
    .sqv3-deal__oldprice {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 6.25cqw;             /* 19.9979 */
        line-height: 1.2501;            /* 25 / 19.9979 */
        text-decoration-line: line-through;
        color: #8F8F8F;
        white-space: nowrap;
    }
    /* Discount chip: 76 x 40.91. Sections may override the colours inline -
       Best Seller cycles #DE1709 / #FF570F / #FFB00A across its cards. */
    .sqv3-deal__discount {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 0 2.5cqw;
        height: 12.79cqw;
        background: #FFB00A;
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 5.479cqw;            /* 17.5315 */
        line-height: 1.2549;            /* 22 / 17.5315 */
        letter-spacing: 0.730481px;
        color: #121212;
        white-space: nowrap;
        flex: none;
    }
</style>
@endonce
