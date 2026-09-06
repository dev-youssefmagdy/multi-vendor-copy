// Swiper wiring for the ELORA home screen carousels.
// Product card markup is now rendered server-side in the Blade sections
// (resources/views/themes/elora/pages/home-v2/sections/*.blade.php and their
// partials/ subfolder) instead of being generated here from JS data.

function mountTrending() {
    const wrapper = document.getElementById("trendingWrapper");
    if (!wrapper) return;

    return new Swiper(wrapper.closest(".swiper"), {
        slidesPerView: "auto",
        spaceBetween: 16,
        navigation: { prevEl: "#trendingPrev", nextEl: "#trendingNext" },
    });
}

function mountFeatures() {
    const wrapper = document.getElementById("featuresWrapper");
    if (!wrapper) return;

    return new Swiper(wrapper.closest(".swiper"), {
        slidesPerView: "auto",
        spaceBetween: 32,
        breakpoints: {
            1024: { slidesPerView: 3.5, spaceBetween: 52.39 },
        },
    });
}

// Best Seller: centered/focus carousel — whichever slide is currently active
// (centered) scales up to full size and full opacity; neighbors on either side
// sit scaled down and muted, partially visible. The scale/opacity live on
// .swiper-slide-active vs. the default slide state in styles.css, driven purely
// by Swiper's own active-slide class as it scrolls (not by a fixed per-index
// layout), so the "focused" card is always whichever one is centered.

// Best Seller positions every slide manually (absolute + translateX + scale)
// based purely on its live distance from the active slide, instead of relying
// on Swiper's box-flow layout (flex widths/spaceBetween/centeredSlides). That
// box-flow approach kept fighting the visual scale-down — since CSS transform
// never changes the box Swiper uses for positioning — leaving gaps at the
// outer edges and letting a 6th slide peek in no matter how the spacing was
// tuned. virtualTranslate:true stops Swiper from touching the DOM itself, but
// it still tracks drag/progress internally, so slide.progress stays accurate
// for us to position from directly. This guarantees exactly 5 fully visible,
// evenly overlapping slides, symmetric around whichever one is centered.
const BEST_SELLER_STEP_PX = 145;

function mountBestSeller() {
    const wrapper = document.getElementById("bestSellerWrapper");
    if (!wrapper) return;

    // Swiper measures each slide's natural (in-flow) position/width the first
    // time it runs updateSlides() — during construction, before this "init"
    // handler fires — so switching them to position:absolute only here (not via
    // a static CSS rule) keeps that initial measurement correct while still
    // achieving the fully custom, gap-free final layout. Progress is read for
    // every slide FIRST, in its own pass, before any slide is repositioned —
    // absolutizing one slide mid-loop shifts the still-in-flow slides after it,
    // corrupting their progress reading if done in a single combined pass.
    const applyLayout = (sw) => {
        const progresses = sw.slides.map((slideEl) => slideEl.progress || 0);
        sw.slides.forEach((slideEl, i) => {
            const progress = progresses[i];
            const dist = Math.min(Math.abs(progress), 2);
            const scale = 1 - dist * 0.22;
            const offset = progress * BEST_SELLER_STEP_PX;
            slideEl.style.position = "absolute";
            slideEl.style.top = "50%";
            slideEl.style.left = "50%";
            slideEl.style.transform = `translate(-50%, -50%) translateX(${offset}px) scale(${scale})`;
            slideEl.style.zIndex = String(10 - Math.round(dist * 4));
        });
    };

    return new Swiper(wrapper.closest(".swiper"), {
        slidesPerView: "auto",
        centeredSlides: true,
        initialSlide: 2,
        virtualTranslate: true,
        watchSlidesProgress: true,
        on: {
            init: applyLayout,
            progress: applyLayout,
            setTransition(sw, duration) {
                sw.slides.forEach((slideEl) => {
                    slideEl.style.transitionDuration = `${duration}ms`;
                });
            },
        },
    });
}

// Flash Sale: fanned-card carousel matching the reference design — the
// active card is centered, upright and fully visible in front; the card
// behind it on the left rotates counter-clockwise (negative degrees) and
// the one on the right rotates clockwise (positive degrees), both shrunk
// and mostly hidden behind the active card so only a sliver of each peeks
// out — exactly 3 cards visible, heavily overlapped, clipped to a track
// just wide enough for that silhouette. Unlike Best Seller above, this does
// NOT use virtualTranslate + position:absolute: that combo takes slides out
// of normal flow, which changes what Swiper measures for each slide's
// offset — since we still have watchSlidesProgress on, that triggers a NEW
// progress event, which re-runs this same layout, which changes the
// measured offsets again, and so on. It free-runs into a slow feedback loop
// that erodes every slide down to the same collapsed position within
// roughly a second (confirmed by isolated testing — Best Seller's identical
// technique degrades the same way, just less visibly). Applying only
// `transform: rotate()/scale()` — decorative, not layout — leaves Swiper's
// own real translate and offset cache alone, so there is nothing for this
// to feed back into.
const FLASH_SALE_MAX_ROTATE_DEG = 15;

function mountFlashSale() {
    const wrapper = document.getElementById("flashSaleWrapper");
    if (!wrapper) return;

    // Distance is each slide's own index minus the active index — a plain
    // integer, not Swiper's continuous per-slide `progress`. With a card
    // this wide overlapped by a spaceBetween this negative, the effective
    // step between slides is tiny relative to the card width, and Swiper's
    // progress (normalized against slide size) ends up asymmetric — the
    // "prev" slide reliably reads progress ~1 but "next" reads ~0, so only
    // one side ever rotated. Index math sidesteps that entirely: it can't
    // drift or come out lopsided, it's just i - activeIndex.
    //
    // Rotation is mirrored across the active card: the card to its left
    // (lower index, distSigned < 0) rotates on the negative axis, the card
    // to its right (higher index, distSigned > 0) rotates on the positive
    // axis. Anything beyond the immediate neighbor (dist > 1) is
    // force-hidden with opacity so exactly 3 cards are ever visible, never
    // a 4th sliver, regardless of how the container width and spaceBetween
    // happen to interact.
    const applyLayout = (sw) => {
        sw.slides.forEach((slideEl, i) => {
            const distSigned = i - sw.activeIndex;
            const dist = Math.min(Math.abs(distSigned), 2);
            const scale = 1 - dist * 0.18;
            const rotate = Math.max(
                -FLASH_SALE_MAX_ROTATE_DEG,
                Math.min(FLASH_SALE_MAX_ROTATE_DEG, distSigned * FLASH_SALE_MAX_ROTATE_DEG),
            );
            slideEl.style.transform = `rotate(${rotate}deg) scale(${scale})`;
            slideEl.style.zIndex = String(10 - Math.round(dist * 4));
            slideEl.style.opacity = dist > 1 ? "0" : "1";
        });
    };

    return new Swiper(wrapper.closest(".swiper"), {
        slidesPerView: "auto",
        centeredSlides: true,
        initialSlide: 1,
        spaceBetween: -111,
        loop: true,
        loopAdditionalSlides: 2,
        breakpoints: {
            1024: { spaceBetween: -210 },
        },
        on: {
            init: applyLayout,
            slideChangeTransitionStart: applyLayout,
            setTransition(sw, duration) {
                sw.slides.forEach((slideEl) => {
                    slideEl.style.transitionDuration = `${duration}ms`;
                });
            },
        },
    });
}

function mountCarousel({ wrapperId, prevId, nextId }) {
    const wrapper = document.getElementById(wrapperId);
    if (!wrapper) return;

    return new Swiper(wrapper.closest(".swiper"), {
        slidesPerView: "auto",
        spaceBetween: 16,
        navigation: {
            prevEl: `#${prevId}`,
            nextEl: `#${nextId}`,
        },
    });
}

function initCarousels() {
    mountCarousel({
        wrapperId: "newInWrapper",
        prevId: "newInPrev",
        nextId: "newInNext",
    });
    mountTrending();
    mountFlashSale();
    mountBestSeller();
    mountFeatures();

    // Flash-sale countdown (starts from the design's captured 03:06:25 and ticks down)
    let remaining = 3 * 3600 + 6 * 60 + 25;
    const timerEl = document.getElementById("flashTimer");
    if (timerEl) {
        setInterval(() => {
            remaining = Math.max(0, remaining - 1);
            const h = String(Math.floor(remaining / 3600)).padStart(2, "0");
            const m = String(Math.floor((remaining % 3600) / 60)).padStart(
                2,
                "0",
            );
            const s = String(remaining % 60).padStart(2, "0");
            timerEl.textContent = `${h}:${m}:${s}`;
        }, 1000);
    }
}

let carouselsInitialized = false;
function initCarouselsOnce() {
    if (carouselsInitialized) return;
    carouselsInitialized = true;
    initCarousels();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCarouselsOnce);
} else {
    initCarouselsOnce();
}
window.addEventListener("load", initCarouselsOnce);
