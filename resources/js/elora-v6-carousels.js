// Swiper wiring for the ELORA "New In" home screen (Screen 2) carousels.
// Product card markup is rendered server-side in the Blade sections
// (resources/views/themes/elora/pages/home-v6/sections/*.blade.php and their
// partials/ subfolder), so this file only mounts Swiper on the existing DOM.

function mountCarousel({ wrapperId, prevId, nextId }) {
    const wrapper = document.getElementById(wrapperId);
    if (!wrapper) return;
    return new Swiper(wrapper.closest(".swiper"), {
        slidesPerView: "auto",
        spaceBetween: 16,
        ...(prevId && nextId
            ? { navigation: { prevEl: `#${prevId}`, nextEl: `#${nextId}` } }
            : {}),
    });
}

// Trending Now: numeric slidesPerView on both breakpoints — 1.25 on mobile
// (one card plus a peek of the next), 2.5 on desktop.
function mountTrending() {
    const wrapper = document.getElementById("trendingWrapper");
    if (!wrapper) return;
    return new Swiper(wrapper.closest(".swiper"), {
        slidesPerView: 1.25,
        spaceBetween: 16,
        slidesOffsetAfter: 16,
        breakpoints: {
            1024: { slidesPerView: 2.5 },
        },
    });
}

// Categories: fixed-width pill carousel, spacing matches the Figma 12px
// (mobile) / 22px (desktop, lg breakpoint) gap.
function mountCategories() {
    const wrapper = document.getElementById("categoriesWrapper");
    if (!wrapper) return;
    return new Swiper(wrapper.closest(".swiper"), {
        slidesPerView: "auto",
        spaceBetween: 12,
        breakpoints: {
            1024: { slidesPerView: 7, spaceBetween: 22 },
        },
    });
}

// Flash Sale: Swiper Grid module — each slide is a column of 3 stacked
// products (grid rows:3) on both breakpoints. slidesPerView:1.5 on mobile
// (1.5 columns visible, i.e. a peek of the next column's 3 cards) scaling
// up to slidesPerView:3 on desktop (3 full columns = 9 products per
// view/page). No nav buttons; swipe/drag pages between views.
function mountFlashGrid() {
    const grid = document.getElementById("flashGrid");
    if (!grid) return;
    return new Swiper(grid.closest(".swiper"), {
        slidesPerView: 1.5,
        spaceBetween: 12,
        grid: { rows: 3, fill: "row" },
        breakpoints: {
            1024: { slidesPerView: 3 },
        },
    });
}

// Best Seller: each carousel slide is a composite of 4 products — one full
// card on the left, and a right-hand column split into 2 small cards on top
// plus 1 wide horizontal card on the bottom (matching the Figma layout).
// The composite's cards each have their own fixed px size (see their Blade
// partials), so slidesPerView must stay "auto" — a numeric value would force
// each slide's width to container/N regardless of the fixed-size content,
// leaving gaps or clipping (see .best-seller-swiper .swiper-slide in
// home-v6.css for the matching width:auto override).
function mountBestSeller() {
    const wrapper = document.getElementById("bestSellerWrapper");
    if (!wrapper) return;
    return new Swiper(wrapper.closest(".swiper"), {
        slidesPerView: "auto",
        spaceBetween: 16,
    });
}

function initCarousels() {
    mountCarousel({ wrapperId: "newInWrapper" });
    mountCategories();
    mountBestSeller();
    mountTrending();
    mountFlashGrid();

    // Flash-sale countdown (starts from the design's captured 03:06:25 and ticks down)
    let remaining = 3 * 3600 + 6 * 60 + 25;
    const timerEl = document.getElementById("flashTimerH");
    const timerElM = document.getElementById("flashTimerM");
    const timerElS = document.getElementById("flashTimerS");
    if (timerEl && timerElM && timerElS) {
        setInterval(() => {
            remaining = Math.max(0, remaining - 1);
            const h = String(Math.floor(remaining / 3600)).padStart(2, "0");
            const m = String(Math.floor((remaining % 3600) / 60)).padStart(
                2,
                "0",
            );
            const s = String(remaining % 60).padStart(2, "0");
            timerEl.textContent = h;
            timerElM.textContent = m;
            timerElS.textContent = s;
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
