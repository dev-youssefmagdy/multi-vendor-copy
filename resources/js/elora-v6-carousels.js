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

// Trending Now: "auto" on mobile (slide width pinned via CSS, one wide card
// peeking) so it shows the full-size card; numeric 2.5 on desktop, letting
// Swiper compute the slide width itself (matches the Categories pattern —
// mixing a numeric slidesPerView with an !important CSS width causes a
// runaway ResizeObserver feedback loop, so the CSS width rule for this slide
// must stay non-important, see home-v6.css).
function mountTrending() {
    const wrapper = document.getElementById("trendingWrapper");
    if (!wrapper) return;
    return new Swiper(wrapper.closest(".swiper"), {
        slidesPerView: "auto",
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

// Flash Sale: real Swiper carousel using the Grid module — slidesPerView:3
// (3 columns visible) x grid rows:3 = 9 products per view/page. 18 items is
// an exact multiple of 9, so it pages cleanly across exactly 2 full views
// with no partial/uneven row. No nav buttons; swipe/drag pages between views.
function mountFlashGrid() {
    const grid = document.getElementById("flashGrid");
    if (!grid) return;
    return new Swiper(grid.closest(".swiper"), {
        slidesPerView: 3,
        spaceBetween: 12,
        grid: { rows: 3, fill: "row" },
    });
}

// Best Seller: each carousel slide is a composite of 4 products — one full
// card on the left, and a right-hand column split into 2 small cards on top
// plus 1 wide horizontal card on the bottom (matching the Figma layout).
function mountBestSeller() {
    const wrapper = document.getElementById("bestSellerWrapper");
    if (!wrapper) return;
    return new Swiper(wrapper.closest(".swiper"), {
        slidesPerView: 1.75,
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
