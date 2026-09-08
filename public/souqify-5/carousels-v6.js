// Swiper wiring for the Souqify home screen (v6).
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
    // Phones show two features and a sliver of the third, so the strip reads as
    // swipeable rather than as a single centred item.
    slidesPerView: 2.3,
    spaceBetween: 10,
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
      640: { slidesPerView: 3, spaceBetween: 24 },
      1024: { slidesPerView: 3.5, spaceBetween: 52.39 },
    },
  });
}

function mountCategories() {
  const wrapper = document.getElementById("categoriesWrapper");
  if (!wrapper) return;
  const swiperEl = wrapper.closest(".swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    // Phones show exactly four tiles; a swipe replaces them with the next four.
    slidesPerView: 4,
    slidesPerGroup: 4,
    spaceBetween: 12,
    breakpoints: {
      640: { slidesPerView: 5, slidesPerGroup: 5, spaceBetween: 16 },
      // The comp's 1347px row holds all eight tiles at a 24px gap.
      1024: { slidesPerView: 8, slidesPerGroup: 8, spaceBetween: 24 },
    },
  });
}

function mountFlash() {
  const wrapper = document.getElementById("flashWrapper");
  if (!wrapper) return;
  const swiperEl = wrapper.closest(".swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    // The slide widths come from CSS (each card is narrower than the one before
    // it), so Swiper measures them rather than dividing the track.
    slidesPerView: "auto",
    // Phones fan three cards, desktop six - a swipe swaps the whole set.
    slidesPerGroup: 3,
    spaceBetween: 0,
    breakpoints: {
      1024: { slidesPerGroup: 6 },
    },
  });
}

function mountTrending() {
  const wrapper = document.getElementById("trendingWrapper");
  if (!wrapper) return;
  const swiperEl = wrapper.closest(".swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    // Phones: one whole column plus a sliver of the next.
    slidesPerView: 1.15,
    spaceBetween: 12,
    breakpoints: {
      640: { slidesPerView: 1.8, spaceBetween: 16 },
      // Two whole columns plus half of the third, per the comp.
      1024: { slidesPerView: 2.5, spaceBetween: 20.82 },
    },
  });
}

function mountNewIn() {
  const wrapper = document.getElementById("newInWrapper");
  if (!wrapper) return;
  const swiperEl = wrapper.closest(".swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    // Phones: two whole cards plus a sliver of the third.
    slidesPerView: 2.3,
    spaceBetween: 10,
    breakpoints: {
      640: { slidesPerView: 3.2, spaceBetween: 16 },
      // 4.5 per view: the fifth card is half-cut so the row reads as swipeable.
      1024: { slidesPerView: 4.5, spaceBetween: 19.96 },
    },
  });
}

function mountShopCategory() {
  const wrapper = document.getElementById("shopByCategoryWrapper");
  if (!wrapper) return;
  const swiperEl = wrapper.closest(".swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    slidesPerView: 3,
    slidesPerGroup: 3,
    spaceBetween: 12,
    breakpoints: {
      640: { slidesPerView: 4, slidesPerGroup: 4, spaceBetween: 16 },
      // The comp's 1328px row holds all six tiles at a 16px gap.
      1024: { slidesPerView: 6, slidesPerGroup: 6, spaceBetween: 16 },
    },
  });
}

function mountBestSeller() {
  const wrapper = document.getElementById("bestSellerWrapper");
  if (!wrapper) return;
  const swiperEl = wrapper.closest(".swiper");
  if (!swiperEl) return;

  // Phones show a 2x2 block of four cards and swap all four on a swipe; grid
  // rows cannot be switched from a breakpoint, so the mode is picked at init.
  const grid = !window.matchMedia("(min-width: 1024px)").matches;

  if (grid) {
    swiperEl.classList.add("sqv6-best--grid");
    new Swiper(swiperEl, {
      slidesPerView: 2,
      slidesPerGroup: 2,
      spaceBetween: 12,
      grid: { rows: 2, fill: "row" },
    });
    return;
  }

  new Swiper(swiperEl, {
    // The comp's 1328px row holds exactly five cards at a 21px gap, so a
    // swipe swaps the whole set of five for the next five.
    slidesPerView: 5,
    slidesPerGroup: 5,
    spaceBetween: 21,
  });
}

function initCarouselsV6() {
  mountTrustBar();
  mountCategories();
  mountFlash();
  mountNewIn();
  mountShopCategory();
  mountTrending();
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

let carouselsV6Initialized = false;
function initCarouselsV6Once() {
  if (carouselsV6Initialized) return;
  carouselsV6Initialized = true;
  initCarouselsV6();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initCarouselsV6Once);
} else {
  initCarouselsV6Once();
}
window.addEventListener("load", initCarouselsV6Once);
