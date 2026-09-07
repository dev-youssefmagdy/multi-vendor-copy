@once
{{-- Shared styles for the v3 "Mobile Card" (Figma 496.21x156.48). Used by the
     Trending Now row and by the Flash Sale carousel, so they live in their own
     @once partial rather than inside either section. --}}
<style>
    /* ---------- Card ---------- */
    .sqv3-trend-card {
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        padding: 0;
        width: 100%;
        background: #FDFDFD;
        border-radius: 10.4358px;
        overflow: hidden;
    }
    .sqv3-trend-card__media {
        position: relative;
        display: block;
        /* 215.51 / 496.21 */
        width: 43.43%;
        flex: none;
        align-self: stretch;
        background: var(--color-card-image-bg);
        border-radius: 8.7462px 8.7462px 0px 0px;
        overflow: hidden;
    }
    .sqv3-trend-card__img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .sqv3-trend-card__sale {
        position: absolute;
        top: 0;
        right: 0;
        z-index: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 5.75017px;
        background: #FFD428;
        border-radius: 0px 7.66689px;
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 10px;
        line-height: 1.2670;            /* 17 / 13.4171 */
        letter-spacing: 0.479181px;
        color: #242424;
        white-space: nowrap;
    }
    .sqv3-trend-card__heart {
        position: absolute;
        top: 5.46638px;
        left: 6.55965px;
        z-index: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 5px;
        width: 26px;
        height: 26px;
        background: #FFFFFF;
        box-shadow: 0px 4.3731px 4.3731px rgba(0, 0, 0, 0.15);
        border-radius: 54.6638px;
        border: 0;
        cursor: pointer;
    }
    .sqv3-trend-card__heart img { width: 100%; height: 100%; object-fit: contain; }
    .sqv3-trend-card__cart {
        position: absolute;
        bottom: 5.46638px;
        right: 6.55965px;
        z-index: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 4.3731px 9px;
        width: 44px;
        height: 35px;
        background: #FDFDFD;
        border-radius: 17.4924px;
        border: 0;
        cursor: pointer;
    }
    .sqv3-trend-card__cart img { width: 100%; height: 100%; object-fit: contain; }

    .sqv3-trend-card__info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        padding: 6.95721px 8px;
        gap: 6px;
        flex: 1;
        min-width: 0;
    }
    .sqv3-trend-card__head,
    .sqv3-trend-card__meta,
    .sqv3-trend-card__status {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 4.37px;
        width: 100%;
        min-width: 0;
    }
    .sqv3-trend-card__titlerow {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 2.19px;
        width: 100%;
        min-width: 0;
    }
    .sqv3-trend-card__name {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 13px;
        line-height: 1.2457;            /* 26 / 20.8716 */
        letter-spacing: 0.546638px;
        color: #121212;
        text-decoration: none;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sqv3-trend-card__weight {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 12px;
        line-height: 1.5523;            /* 27 / 17.393 */
        letter-spacing: 0.546638px;
        color: var(--color-souqify-teal);
        flex: none;
    }
    .sqv3-trend-card__desc {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 11px;
        line-height: 1.2649;            /* 22 / 17.393 */
        letter-spacing: 0.546638px;
        color: #ADADAD;
        margin: 0;
        width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sqv3-trend-card__rating {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 8.75px;
    }
    .sqv3-trend-card__stars {
        /* 74.88 x 10.93 in the comp */
        width: 54px;
        height: 7.88px;
        flex: none;
    }
    .sqv3-trend-card__ratingtext {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 11px;
        line-height: 1.2936;            /* 18 / 13.9144 */
        letter-spacing: 0.546638px;
        color: #ADADAD;
        white-space: nowrap;
    }
    .sqv3-trend-card__prices {
        display: flex;
        flex-direction: row;
        align-items: flex-end;
        gap: 8.75px;
        flex-wrap: wrap;
    }
    .sqv3-trend-card__price {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 15px;
        line-height: 1.2457;            /* 26 / 20.8716 */
        color: var(--color-souqify-teal);
        white-space: nowrap;
    }
    .sqv3-trend-card__oldprice {
        font-family: 'Outfit', sans-serif;
        font-weight: 300;
        font-size: 11px;
        line-height: 1.2413;            /* 19 / 15.3059 */
        text-decoration-line: line-through;
        color: #ADADAD;
        white-space: nowrap;
    }
    .sqv3-trend-card__discount {
        font-family: 'Outfit', sans-serif;
        font-weight: 400;
        font-size: 11px;
        line-height: 1.2936;
        letter-spacing: 0.546638px;
        color: #FF522C;
        white-space: nowrap;
    }

    .sqv3-trend-card__statusrow {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 6.96px;
        min-width: 0;
    }
    .sqv3-trend-card__statusrow--stock { gap: 8.75px; }
    .sqv3-trend-card__truck { width: 15px; height: 15px; flex: none; }
    .sqv3-trend-card__cartx { width: 13px; height: 13px; flex: none; }
    .sqv3-trend-card__statustext {
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
        font-size: 11px;
        line-height: 1.2936;            /* 18 / 13.9144 */
        color: #2AAF2F;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sqv3-trend-card__statustext--sm {
        font-size: 10px;
        line-height: 1.2958;            /* 17 / 13.1193 */
    }

    @media (min-width: 1024px) {
        .sqv3-trend-card {
            /* 156.48 tall; width comes from Swiper's slidesPerView (1328 / 496.21
               = 2.68 columns across the padded row, matching the comp). */
            height: clamp(120px, 10.8667vw, 156.48px);
        }
        .sqv3-trend-card__sale {
            /* 13.4171px */
            font-size: clamp(10px, 0.9317vw, 13.4171px);
        }
        .sqv3-trend-card__heart {
            /* 34.98 box */
            width: clamp(26px, 2.4292vw, 34.98px);
            height: clamp(26px, 2.4292vw, 34.98px);
        }
        .sqv3-trend-card__cart {
            /* 62.32 x 49.2, 13.1193px side padding */
            width: clamp(44px, 4.3278vw, 62.32px);
            height: clamp(35px, 3.4167vw, 49.2px);
            padding: 4.3731px clamp(9px, 0.9111vw, 13.1193px);
        }
        .sqv3-trend-card__info {
            /* 6.95721px padding, 6.96px gap */
            padding: 6.95721px;
            gap: 6.96px;
        }
        .sqv3-trend-card__name   { font-size: clamp(13px, 1.4494vw, 20.8716px); }
        .sqv3-trend-card__weight,
        .sqv3-trend-card__desc   { font-size: clamp(11px, 1.2079vw, 17.393px); }
        .sqv3-trend-card__stars {
            /* 74.88 x 10.93 */
            width: clamp(54px, 5.2vw, 74.88px);
            height: clamp(7.88px, 0.7590vw, 10.93px);
        }
        .sqv3-trend-card__ratingtext,
        .sqv3-trend-card__discount { font-size: clamp(11px, 0.9663vw, 13.9144px); }
        .sqv3-trend-card__price    { font-size: clamp(15px, 1.4494vw, 20.8716px); }
        .sqv3-trend-card__oldprice { font-size: clamp(11px, 1.0629vw, 15.3059px); }
        .sqv3-trend-card__truck {
            width: clamp(15px, 1.4493vw, 20.87px);
            height: clamp(15px, 1.4493vw, 20.87px);
        }
        .sqv3-trend-card__cartx {
            width: clamp(13px, 1.2146vw, 17.49px);
            height: clamp(13px, 1.2146vw, 17.49px);
        }
        .sqv3-trend-card__statustext     { font-size: clamp(11px, 0.9663vw, 13.9144px); }
        .sqv3-trend-card__statustext--sm { font-size: clamp(10px, 0.9111vw, 13.1193px); }
    }
</style>
@endonce
