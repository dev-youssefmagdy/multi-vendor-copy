// Swiper wiring for the Souqify home screen (v4).
// The product cards in these wrappers are server-rendered by Blade with real product
// data, so this file ONLY initializes Swiper on the existing DOM nodes — it does not
// inject any hardcoded card HTML.

function mountNewIn() {
  const wrapper = document.getElementById("newInWrapper");
  if (!wrapper) return;
  const swiperEl = wrapper.closest(".swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    // Mobile comp: two whole cards plus a sliver of the next.
    slidesPerView: 2.15,
    spaceBetween: 12,
    breakpoints: {
      // 17px is the comp's gap between the 226.67px cards.
      640: { slidesPerView: 3.15, spaceBetween: 17 },
      1024: { slidesPerView: 5.5, spaceBetween: 17 },
    },
  });
}

function mountTrustBar() {
  const swiperEl = document.querySelector(".trust-swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    slidesPerView: 1.2,
    spaceBetween: 24,
    loop: true,
    // delay:0 + a long speed makes autoplay a continuous marquee rather than a
    // slide-pause-slide step. Respects the user's reduced-motion preference.
    autoplay: window.matchMedia("(prefers-reduced-motion: reduce)").matches
      ? false
      : { delay: 0, disableOnInteraction: false, pauseOnMouseEnter: true },
    speed: 4000,
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
    slidesPerView: 1.1,
    spaceBetween: 16,
    breakpoints: {
      640: { slidesPerView: 1.8, spaceBetween: 16 },
      // 1328px of padded row / 463.76px card = 2.86 cards across, matching the comp.
      1024: { slidesPerView: 2.86, spaceBetween: 16 },
    },
  });
}

function mountShopCategory() {
  const wrapper = document.getElementById("shopCategoryWrapper");
  if (!wrapper) return;
  const swiperEl = wrapper.closest(".swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    // Mobile comp shows three whole tiles and swipes to the next set.
    slidesPerView: 3,
    spaceBetween: 8,
    breakpoints: {
      // 7 whole tiles plus a narrow sliver of the next, so the row reads as
      // scrollable without showing a half-cut tile.
      640: { slidesPerView: 4.15, spaceBetween: 19.87 },
      1024: { slidesPerView: 7.15, spaceBetween: 19.87 },
    },
  });
}

function mountBestSeller() {
  const group = document.getElementById("bestSellerWrapper");
  if (!group) return;
  const dotsRow = group.parentElement.querySelector(".sqv4-best__dots");
  if (!dotsRow) return;

  // The mobile fan shows three cards at a time and scrolls a page at a time; the
  // dots track pages, not cards. Desktop keeps the static comp fan.
  const isMobile = () => window.innerWidth < 1024;
  let dots = [];

  const pageCount = () =>
    Math.max(1, Math.round(group.scrollWidth / group.clientWidth));

  const buildDots = () => {
    const count = isMobile() ? pageCount() : dotsRow.dataset.cardCount | 0;
    if (dots.length === count) return;
    dotsRow.innerHTML = "";
    dots = Array.from({ length: count }, (_, i) => {
      const dot = document.createElement("span");
      dot.className = "best-seller-dot" + (i === 0 ? " is-active" : "");
      dot.addEventListener("click", () => {
        group.scrollTo({ left: i * group.clientWidth, behavior: "smooth" });
      });
      dotsRow.appendChild(dot);
      return dot;
    });
  };

  const syncActive = () => {
    if (!dots.length) return;
    const index = Math.round(group.scrollLeft / group.clientWidth);
    dots.forEach((dot, i) => dot.classList.toggle("is-active", i === index));
  };

  dotsRow.dataset.cardCount = String(dotsRow.children.length);
  buildDots();
  group.addEventListener("scroll", syncActive, { passive: true });
  window.addEventListener("resize", () => {
    buildDots();
    syncActive();
  });
}

function initCarouselsV4() {
  mountNewIn();
  mountBestSeller();
  mountShopCategory();
  mountTrustBar();
  mountTrending();

  // Flash-sale countdown: prefer the server-provided end timestamp (data-flash-end on
  // the section), falling back to the design's captured 03:06:25 if no active flash sale.
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

let carouselsV4Initialized = false;
function initCarouselsV4Once() {
  if (carouselsV4Initialized) return;
  carouselsV4Initialized = true;
  initCarouselsV4();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initCarouselsV4Once);
} else {
  initCarouselsV4Once();
}
window.addEventListener("load", initCarouselsV4Once);
