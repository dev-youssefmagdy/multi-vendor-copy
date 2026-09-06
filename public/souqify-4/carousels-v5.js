// Swiper wiring for the Souqify home screen (v5).
// The product cards in these wrappers are server-rendered by Blade with real
// product data, so this file ONLY initializes Swiper on the existing DOM
// nodes — it does not inject any hardcoded card HTML. The "Recommended For
// You" section is a plain CSS grid with Livewire scroll-pagination
// (wire:intersect), so it is intentionally not mounted here.

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
    slidesPerView: 1.5,
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

function mountFlash() {
  const swiperEl = document.querySelector(".flash-swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    // Each slide is a column of two stacked cards. Phones show one whole column
    // and a swipe swaps in the next two cards - no peek, so nothing is cut.
    slidesPerView: 1,
    slidesPerGroup: 1,
    spaceBetween: 16,
    breakpoints: {
      640: { slidesPerView: 2.15, spaceBetween: 24 },
      1024: { slidesPerView: 3.35, spaceBetween: 30.4 },
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
      // The comp's 1347px row holds all eight tiles at a 24px gap, so desktop
      // shows the full set and only narrower screens scroll.
      640: { slidesPerView: 6, spaceBetween: 16 },
      1024: { slidesPerView: 8, spaceBetween: 24 },
    },
  });
}

function mountTrending() {
  const wrapper = document.getElementById("trendingWrapper");
  if (!wrapper) return;
  const swiperEl = wrapper.closest(".swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    // Figma runs 5.5 cards per view: the sixth is half-cut so the row reads as
    // swipeable. Card width follows from slidesPerView, not a fixed px width.
    slidesPerView: 2.3,
    spaceBetween: 12,
    breakpoints: {
      640: { slidesPerView: 3.2, spaceBetween: 12 },
      1024: { slidesPerView: 5.5, spaceBetween: 12 },
    },
  });
}

function mountShopCategory() {
  const swiperEl = document.querySelector(".shopcat-swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    // Three whole tiles per phone screen; a swipe brings the next three.
    slidesPerView: 3,
    slidesPerGroup: 3,
    spaceBetween: 12,
    breakpoints: {
      // The comp's 1328px row holds all seven tiles at a 25.96px gap, so desktop
      // shows the full set and only narrower screens scroll.
      640: { slidesPerView: 5, spaceBetween: 16 },
      1024: { slidesPerView: 7, spaceBetween: 25.96 },
    },
  });
}

function mountNewIn() {
  const wrapper = document.getElementById("newInWrapper");
  if (!wrapper) return;
  const swiperEl = wrapper.closest(".swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    // One whole column (three cards) per phone screen, and a swipe brings the
    // next three - no peek, so no card is cut.
    slidesPerView: 1,
    slidesPerGroup: 1,
    spaceBetween: 12,
    breakpoints: {
      640: { slidesPerView: 1.6, spaceBetween: 12 },
      // Two full columns plus half of the third, per the comp.
      1024: { slidesPerView: 2.5, spaceBetween: 12 },
    },
  });
}

function mountBestSeller() {
  const wrapper = document.getElementById("bestSellerWrapper");
  if (!wrapper) return;
  const swiperEl = wrapper.closest(".swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    // Three fanned cards per phone screen, overlapping as in the comp; a swipe
    // replaces all three. The negative gap is what makes them overlap.
    slidesPerView: 3,
    slidesPerGroup: 3,
    spaceBetween: -22,
    breakpoints: {
      640: { slidesPerView: 3, slidesPerGroup: 3, spaceBetween: -18 },
      // Whole cards only - the tilt already reads as a fan, and a negative gap
      // put the first card half under the panel edge.
      1024: { slidesPerView: 4, spaceBetween: 24 },
    },
  });
}

function initCarouselsV5() {
  mountTrustBar();
  mountCategories();
  mountFlash();
  mountNewIn();
  mountTrending();
  mountShopCategory();
  mountBestSeller();

  // Flash-sale countdown: prefer the server-provided end timestamp
  // (data-flash-end on the section), falling back to the design's captured
  // 03:06:25 if no active flash sale.
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

let carouselsV5Initialized = false;
function initCarouselsV5Once() {
  if (carouselsV5Initialized) return;
  carouselsV5Initialized = true;
  initCarouselsV5();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initCarouselsV5Once);
} else {
  initCarouselsV5Once();
}
window.addEventListener("load", initCarouselsV5Once);
