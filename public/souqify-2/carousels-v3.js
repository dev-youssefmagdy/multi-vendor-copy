// Swiper wiring for the Souqify home screen (v3).
// Unlike the static mockup's carousels.js, the product cards in these wrappers are now
// server-rendered by Blade with real product data, so this file ONLY initializes Swiper
// on the existing DOM nodes — it no longer injects any hardcoded card HTML.

function mountSwiper(wrapperId, prevId, nextId) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;
  const swiperEl = wrapper.closest(".swiper");
  if (!swiperEl) return;

  return new Swiper(swiperEl, {
    slidesPerView: "auto",
    spaceBetween: 16,
    navigation: {
      prevEl: prevId ? `#${prevId}` : undefined,
      nextEl: nextId ? `#${nextId}` : undefined,
    },
  });
}

function mountTrustBar() {
  const swiperEl = document.querySelector(".trust-swiper");
  if (!swiperEl) return;

  // The marquee is a desktop-only effect: mobile shows one feature at a time and
  // the user swipes between them, so autoplay stays off there. Evaluated once at
  // init - Swiper cannot toggle autoplay from a breakpoint.
  const marquee =
    window.matchMedia("(min-width: 1024px)").matches &&
    !window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  new Swiper(swiperEl, {
    // Mobile shows a single feature; the user swipes between them.
    slidesPerView: 1,
    spaceBetween: 24,
    loop: true,
    // delay:0 + a long speed makes autoplay a continuous marquee rather than a
    // slide-pause-slide step.
    autoplay: marquee
      ? { delay: 0, disableOnInteraction: false, pauseOnMouseEnter: true }
      : false,
    speed: marquee ? 4000 : 300,
    allowTouchMove: true,
    breakpoints: {
      // 52.39px spaceBetween is the Figma gap between feature blocks.
      640: { slidesPerView: 2.5, spaceBetween: 32 },
      1024: { slidesPerView: 3.5, spaceBetween: 52.39 },
    },
  });
}

function mountTrending() {
  const swiperEl = document.querySelector(".trending-swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    // Mobile: one column just over two thirds of the row, so the next column's
    // edge shows past the midpoint and the row reads as swipeable.
    slidesPerView: 1.3,
    spaceBetween: 16,
    breakpoints: {
      640: { slidesPerView: 1.8, spaceBetween: 16 },
      // 1328px of padded row / 496.21px card column = 2.68 columns across,
      // matching the comp. 20.87px is the Figma gap between columns.
      1024: { slidesPerView: 2.68, spaceBetween: 20.87 },
    },
  });
}

function mountCategories() {
  const swiperEl = document.querySelector(".categories-swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    // Mobile fits four whole tiles; the rest swipe in.
    slidesPerView: 4,
    spaceBetween: 12,
    breakpoints: {
      // 48px spaceBetween is the Figma gap between category tiles. The .5 leaves
      // the last tile half-visible so the row reads as swipeable.
      640: { slidesPerView: 5.5, spaceBetween: 24 },
      1024: { slidesPerView: 7.5, spaceBetween: 48 },
    },
  });
}

function mountFlash() {
  const swiperEl = document.querySelector(".flash-swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    // Slides alternate between a 319.97px deal card and a 510.57px stack of three
    // mobile cards, so widths come from CSS rather than a slidesPerView ratio.
    slidesPerView: "auto",
    spaceBetween: 12,
    breakpoints: {
      // 21.48px is the Figma gap inside Frame 1984079776.
      1024: { spaceBetween: 21.48 },
    },
  });
}

function mountBestSeller() {
  const swiperEl = document.querySelector(".bestseller-swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    // Mobile: two whole cards plus a sliver of the third.
    slidesPerView: 2.15,
    spaceBetween: 12,
    breakpoints: {
      640: { slidesPerView: 3.15, spaceBetween: 16 },
      // 20.18px is the Figma gap inside Frame 1984079779; the .5 leaves the last
      // card half-visible so the row reads as swipeable.
      1024: { slidesPerView: 4.5, spaceBetween: 20.18 },
    },
  });
}

function mountNewIn() {
  const swiperEl = document.querySelector(".newin-swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    // Mobile: two whole cards plus a sliver of the third, matching Best Seller.
    slidesPerView: 2.15,
    spaceBetween: 12,
    breakpoints: {
      640: { slidesPerView: 3.15, spaceBetween: 16 },
      // The comp fits 4.93 cards in its 1328px row; going to 5.3 keeps five whole
      // cards and leaves a slice of the sixth, so the row reads as swipeable.
      // 18.79px is the Figma gap inside Frame 1984079779.
      1024: { slidesPerView: 5.3, spaceBetween: 18.79 },
    },
  });
}

function mountShopCategory() {
  const swiperEl = document.querySelector(".shopcat-swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    // Mobile fits three whole tiles; the rest swipe in.
    slidesPerView: 3,
    spaceBetween: 12,
    breakpoints: {
      640: { slidesPerView: 4.5, spaceBetween: 16 },
      // 24px is the Figma gap inside Frame 1984080365; the .5 leaves the last
      // tile half-visible so the row reads as swipeable.
      1024: { slidesPerView: 5.5, spaceBetween: 24 },
    },
  });
}

function initCarouselsV3() {
  mountTrustBar();
  mountFlash();
  mountNewIn();
  mountTrending();
  mountCategories();
  mountShopCategory();
  mountBestSeller();

  // Flash-sale countdown: prefer the server-provided end timestamp (data-flash-end on the
  // section), falling back to the design's captured 03:06:25 if no active flash sale.
  const flashSection = document.querySelector("[data-flash-end]");
  const hEls = document.querySelectorAll("[data-flash-hours]");
  const mEls = document.querySelectorAll("[data-flash-minutes]");
  const sEls = document.querySelectorAll("[data-flash-seconds]");
  if (hEls.length || mEls.length || sEls.length) {
    let remaining;
    if (flashSection) {
      const endTs = parseInt(flashSection.getAttribute("data-flash-end"), 10) * 1000;
      remaining = Math.max(0, Math.floor((endTs - Date.now()) / 1000));
    } else {
      remaining = 3 * 3600 + 6 * 60 + 25;
    }
    const tick = () => {
      const h = String(Math.floor(remaining / 3600)).padStart(2, "0");
      const m = String(Math.floor((remaining % 3600) / 60)).padStart(2, "0");
      const s = String(remaining % 60).padStart(2, "0");
      hEls.forEach((el) => (el.textContent = h));
      mEls.forEach((el) => (el.textContent = m));
      sEls.forEach((el) => (el.textContent = s));
      remaining = Math.max(0, remaining - 1);
    };
    tick();
    setInterval(tick, 1000);
  }
}

let carouselsV3Initialized = false;
function initCarouselsV3Once() {
  if (carouselsV3Initialized) return;
  carouselsV3Initialized = true;
  initCarouselsV3();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initCarouselsV3Once);
} else {
  initCarouselsV3Once();
}
window.addEventListener("load", initCarouselsV3Once);
