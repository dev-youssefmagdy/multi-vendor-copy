// Swiper wiring + Best Seller fan-cascade positioning for the ELORA "Home" screen (Theme #2).
// Product card markup is rendered server-side in the Blade sections
// (resources/views/themes/elora/pages/home-v5/sections/*.blade.php and their
// partials/ subfolder), so this file only mounts Swiper on the existing DOM
// and drives the interactive bits that can't be expressed as static markup.

function mountFlashSale() {
    const wrapper = document.getElementById("flashSaleWrapper");
    if (!wrapper) return;
    return new Swiper(wrapper.closest(".swiper"), {
        slidesPerView: "auto",
        spaceBetween: 16,
    });
}

// New In: every slide has a fixed !w-[Npx] of its own set in the Blade
// markup, so slidesPerView must stay "auto" — a numeric value would compute
// a pixel width that the fixed class permanently overrides, letting the
// wrapper's real content width run past the container and overflow the page.
function mountNewIn() {
    const wrapper = document.getElementById("newInWrapper");
    if (!wrapper) return;
    return new Swiper(wrapper.closest(".swiper"), {
        slidesPerView: "auto",
        spaceBetween: 16,
        slidesOffsetAfter: 16,
    });
}

// Categories: numeric slidesPerView so the carousel shows a partial next
// slide as a scroll affordance (3.5 on mobile, 7.5 at lg) rather than
// snapping to whole cards like New In's fixed-width slides.
function mountCategories() {
    const wrapper = document.getElementById("categoriesWrapper");
    if (!wrapper) return;
    return new Swiper(wrapper.closest(".swiper"), {
        slidesPerView: 3.5,
        spaceBetween: 16,
        breakpoints: {
            1024: { slidesPerView: 7.5, spaceBetween: 26.58 },
        },
    });
}

// Feature Strip: desktop-only carousel (mobile keeps its own single-item
// auto-rotate below) — numeric slidesPerView shows a partial 4th item as a
// scroll affordance across the 4 feature slides.
function mountFeatureStrip() {
    const wrapper = document.getElementById("featureStripDesktopWrapper");
    if (!wrapper) return;
    return new Swiper(wrapper.closest(".swiper"), {
        slidesPerView: 3.5,
        spaceBetween: 52.39,
    });
}

// Trending Now: same wide-card design as New In, but as a 2-row Swiper Grid
// carousel showing 2.5 columns per view. Grid needs a numeric slidesPerView
// (columns) — unlike New In's slides, these have no fixed !w-[Npx].
function mountTrending() {
    const wrapper = document.getElementById("trendingWrapper");
    if (!wrapper) return;
    return new Swiper(wrapper.closest(".swiper"), {
        slidesPerView: 1.25,
        spaceBetween: 18,
        grid: { rows: 2, fill: "row" },
        slidesOffsetAfter: 16,
        breakpoints: {
            1024: {
                slidesPerView: 2.5,
            },
        },
    });
}

// Best Seller: draggable fan cascade — geometry captured from the Figma card
// stack (node 2007:7743 / 2007:5559), indexed by DISTANCE from the swiper's
// activeIndex (0 = leftmost/biggest ... down to smallest/rightmost), not by
// a fixed product index. Every slide is absolutely positioned from this
// table each time activeIndex changes, so dragging shifts which product
// lands in the "distance 0" (biggest) slot while the rest cascade down —
// matching the original static fan's exact pixel geometry, but now live.
const BEST_SELLER_POSITIONS_DESKTOP = [
    { left: 0, top: 0, width: 284, height: 430 },
    { left: 230, top: 39, width: 256, height: 388 },
    { left: 440, top: 57, width: 244, height: 370 },
    { left: 630, top: 79, width: 230, height: 348 },
    { left: 811, top: 102, width: 215, height: 326 },
    { left: 980, top: 123, width: 203, height: 307 },
];
const BEST_SELLER_POSITIONS_MOBILE = [
    { left: 16, top: 53, width: 206.23, height: 312.52 },
    { left: 172.03, top: 95.01, width: 168.04, height: 254.65 },
    { left: 284.06, top: 121.74, width: 141.94, height: 215.1 },
];

function mountBestSellerCarousel(
    containerId,
    positions,
    { baseWidth, dotsContainerId = null } = {},
) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.style.position = "relative";
    container.style.height = `${positions[0].top + positions[0].height}px`;

    // Only the ORIGINAL slides (captured before Swiper's loop mode injects its
    // own clone slides) get positioned here — clones are neutralized separately
    // below, since our own modulo wrap already makes the cascade infinite
    // without needing Swiper's duplicated DOM nodes to be visually meaningful.
    const slideEls = [...container.children];
    const count = slideEls.length;

    const dotsEl = dotsContainerId
        ? document.getElementById(dotsContainerId)
        : null;
    if (dotsEl) {
        dotsEl.innerHTML = "";
        for (let i = 0; i < count; i++) {
            const dot = document.createElement("button");
            dot.type = "button";
            dot.className = "bestseller-dot" + (i === 0 ? " is-active" : "");
            dot.setAttribute("aria-label", `Product ${i + 1}`);
            dotsEl.appendChild(dot);
        }
    }
    const updateDots = (sw) => {
        if (!dotsEl) return;
        dotsEl.querySelectorAll(".bestseller-dot").forEach((dot, i) => {
            dot.classList.toggle("is-active", i === sw.realIndex);
        });
    };

    const applyPositions = (sw) => {
        slideEls.forEach((slideEl, i) => {
            const distance = (((i - sw.realIndex) % count) + count) % count;
            const box = positions[distance];
            if (!box) {
                // more products than fan slots: park the rest off-screen rather
                // than stacking them at position 0 on top of the active card.
                slideEl.style.opacity = "0";
                slideEl.style.pointerEvents = "none";
                slideEl.style.zIndex = "0";
                return;
            }
            slideEl.style.opacity = "1";
            slideEl.style.pointerEvents = "";
            // Every slide is the SAME base size (.fan-slide-base), positioned
            // and resized purely via transform — scaling the whole card as
            // one unit guarantees every child (text, icons, padding) scales
            // in lockstep, so there's no way for content to overflow its box
            // at any cascade depth.
            const scale = box.width / baseWidth;
            slideEl.style.transform = `translate(${box.left}px, ${box.top}px) scale(${scale})`;
            slideEl.style.zIndex = String(positions.length - distance);
            // Tracks which cascade slot this slide currently occupies (0 = active/front),
            // independent of DOM order — not used for styling any more (the transform
            // above handles all sizing), kept only as a debugging hook.
            slideEl.dataset.cascadePos = String(distance);
        });
        // Loop mode clones aren't part of slideEls and never get positioned —
        // hide them so they don't sit stacked at their default top:0/left:0.
        container
            .querySelectorAll(".swiper-slide-duplicate")
            .forEach((clone) => {
                clone.style.opacity = "0";
                clone.style.pointerEvents = "none";
            });
    };

    return new Swiper(container.closest(".swiper"), {
        slidesPerView: "auto",
        virtualTranslate: true,
        loop: true,
        loopedSlides: count,
        on: {
            init(sw) {
                applyPositions(sw);
                updateDots(sw);
            },
            slideChange(sw) {
                applyPositions(sw);
                updateDots(sw);
            },
            transitionEnd: applyPositions,
            setTransition(sw, duration) {
                slideEls.forEach(
                    (s) => (s.style.transitionDuration = `${duration}ms`),
                );
            },
        },
    });
}

function initCarousels() {
    mountFlashSale();
    mountNewIn();
    mountCategories();
    mountFeatureStrip();
    mountTrending();
    mountBestSellerCarousel(
        "bestSellerMobileWrapper",
        BEST_SELLER_POSITIONS_MOBILE,
        { baseWidth: 206.23, dotsContainerId: "bestSellerMobileDots" },
    );
    mountBestSellerCarousel(
        "bestSellerDesktopWrapper",
        BEST_SELLER_POSITIONS_DESKTOP,
        { baseWidth: 256.37 },
    );

    // Flash-sale countdown (starts from the design's captured 03:06:25 and ticks down)
    let remaining = 3 * 3600 + 6 * 60 + 25;
    const timerEls = document.querySelectorAll("[data-flash-timer]");
    if (timerEls.length) {
        setInterval(() => {
            remaining = Math.max(0, remaining - 1);
            const h = String(Math.floor(remaining / 3600)).padStart(2, "0");
            const m = String(Math.floor((remaining % 3600) / 60)).padStart(
                2,
                "0",
            );
            const s = String(remaining % 60).padStart(2, "0");
            timerEls.forEach((el, i) => {
                el.textContent = [h, m, s][i];
            });
        }, 1000);
    }

    // Mobile feature strip — cycles through the pre-rendered feature items one at a
    // time (matches the design's mobile layout, which shows a single feature versus
    // the desktop's full four-item row) by swapping each item's flex/hidden class.
    const featureItems = document.querySelectorAll(
        "#featureStripMobile .feature-strip-item",
    );
    if (featureItems.length) {
        let featureIndex = 0;
        setInterval(() => {
            featureItems[featureIndex].classList.remove("flex");
            featureItems[featureIndex].classList.add("hidden");
            featureIndex = (featureIndex + 1) % featureItems.length;
            featureItems[featureIndex].classList.remove("hidden");
            featureItems[featureIndex].classList.add("flex");
        }, 3000);
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
