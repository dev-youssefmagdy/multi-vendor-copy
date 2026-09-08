// Swiper wiring for the Souqify home screen (v4).
// The product cards in these wrappers are server-rendered by Blade with real product
// data, so this file ONLY initializes Swiper on the existing DOM nodes — it does not
// inject any hardcoded card HTML.

// One section's mount failing (e.g. missing DOM, a bad selector) must never
// silently skip every mount call that comes after it in initCarouselsV4().
function safeMount(name, fn) {
  try {
    fn();
  } catch (e) {
    console.error(`${name} failed`, e);
  }
}

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

// Flash Sale: same carousel technique as ELORA Bold Edition's Best Seller
// carousel (see elora-v4-carousels.js's mountBestSeller/mountBestSellerMobile) -
// mobile is a swipeable 2-col x 2-row grid (Swiper's grid module), desktop is
// a plain "auto" width swipeable row. No fan/rotation, no scroll-snap paging.
function mountFlashSaleMobile() {
  const wrapper = document.getElementById("flashMobileWrapper");
  if (!wrapper) return;
  const swiperEl = wrapper.closest(".swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    slidesPerView: 2,
    slidesPerGroup: 2,
    spaceBetween: 12,
    grid: { rows: 2, fill: "row" },
  });
}

// Both Flash Sale (desktop) and Best Seller use a draggable fan cascade —
// same technique as ELORA Minimal Edition's Best Seller carousel
// (elora-v5-carousels.js's mountBestSellerCarousel). Geometry is captured
// from Souqify's own Figma card stack, indexed by DISTANCE from the
// swiper's activeIndex (0 = the "front" slot ... down to the last), not by
// a fixed product index. Every slide is absolutely positioned from a table
// each time activeIndex changes, so dragging shifts which product lands in
// the distance-0 slot while the rest cascade around it. Flash Sale's table
// also carries a `rot` per slot (every card stays the same size, just
// rotated/offset); Best Seller's table has no rotation but shrinks size by
// distance instead — mountFanCascadeCarousel supports both.
const SQV4_BEST_SELLER_POSITIONS_DESKTOP = [
  { left: 56, top: 98, width: 295.45, height: 472.89 },
  { left: 329, top: 107, width: 283.96, height: 454.2 },
  { left: 580.18, top: 129, width: 255.24, height: 409 },
  { left: 811, top: 155, width: 229.29, height: 367 },
  { left: 1021, top: 173, width: 210.06, height: 336 },
  { left: 1218, top: 193, width: 192.21, height: 308 },
];
const SQV4_BEST_SELLER_POSITIONS_MOBILE = [
  { left: 0, top: 0, width: 154.5, height: 223.1 },
  { left: 120.75, top: 13.39, width: 141, height: 203.02 },
  { left: 248.25, top: 26.77, width: 127.5, height: 182.94 },
];

// Flash Sale desktop fan: Figma "Group 57" (1176.38 x 456.33) - every card
// is the SAME 228.76 x 366.14 size (no receding), only rotated and offset.
const SQV4_FLASH_POSITIONS_DESKTOP = [
  { left: 0, top: 129.49, rot: -11.98 },     // Deal 4
  { left: 233, top: 96, rot: 0 },            // Deal 17
  { left: 388.33, top: 130.49, rot: 8.97 },  // Deal 16
  { left: 535.33, top: 111.49, rot: -11.98 }, // Deal 20
  { left: 759.33, top: 104.49, rot: 0 },     // Deal 18
  { left: 893.33, top: 155, rot: 8.97 },     // Deal 19
];

function mountFanCascadeCarousel(containerId, positions, { baseWidth, baseHeight = null, dotsContainerId = null } = {}) {
  const container = document.getElementById(containerId);
  if (!container) return;
  container.style.position = "relative";
  // Best Seller's slots each carry their own height (cards shrink by
  // distance); Flash Sale's slots don't (every card is baseHeight), so fall
  // back to the tallest slot's top + baseHeight when a slot has no height.
  container.style.height = `${Math.max(...positions.map((p) => p.top + (p.height ?? baseHeight)))}px`;

  // Only the ORIGINAL slides (captured before Swiper's loop mode injects its
  // own clone slides) get positioned here — clones are neutralized separately
  // below, since our own modulo wrap already makes the cascade infinite
  // without needing Swiper's duplicated DOM nodes to be visually meaningful.
  const slideEls = [...container.children];
  const count = slideEls.length;

  const dotsEl = dotsContainerId ? document.getElementById(dotsContainerId) : null;
  if (dotsEl) {
    dotsEl.innerHTML = "";
    for (let i = 0; i < count; i++) {
      const dot = document.createElement("button");
      dot.type = "button";
      dot.className = "best-seller-dot" + (i === 0 ? " is-active" : "");
      dot.setAttribute("aria-label", `Product ${i + 1}`);
      dotsEl.appendChild(dot);
    }
  }
  const updateDots = (sw) => {
    if (!dotsEl) return;
    dotsEl.querySelectorAll(".best-seller-dot").forEach((dot, i) => {
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
      // Every slide is the SAME base size (a .sqv4-*__deal-base class),
      // positioned and resized purely via transform — scaling the whole
      // card as one unit guarantees every child scales in lockstep. A slot
      // with no `width` (Flash Sale) stays at scale 1 - every card there is
      // already the base size, only rotated/offset.
      const scale = box.width != null ? box.width / baseWidth : 1;
      const rotate = box.rot ? ` rotate(${box.rot}deg)` : "";
      slideEl.style.transform = `translate(${box.left}px, ${box.top}px)${rotate} scale(${scale})`;
      slideEl.style.zIndex = String(positions.length - distance);
    });
    // Loop mode clones aren't part of slideEls and never get positioned —
    // hide them so they don't sit stacked at their default top:0/left:0.
    container.querySelectorAll(".swiper-slide-duplicate").forEach((clone) => {
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
        slideEls.forEach((s) => (s.style.transitionDuration = `${duration}ms`));
      },
    },
  });
}

function mountBestSeller() {
  // Guarded independently — a failure mounting one breakpoint's instance
  // must never prevent the other from mounting.
  try {
    mountFanCascadeCarousel("bestSellerMobileWrapper", SQV4_BEST_SELLER_POSITIONS_MOBILE, {
      baseWidth: 154.5,
      dotsContainerId: "bestSellerMobileDots",
    });
  } catch (e) {
    console.error("mountBestSeller (mobile) failed", e);
  }
  try {
    mountFanCascadeCarousel("bestSellerDesktopWrapper", SQV4_BEST_SELLER_POSITIONS_DESKTOP, {
      baseWidth: 295.45,
    });
  } catch (e) {
    console.error("mountBestSeller (desktop) failed", e);
  }
}

// Flash Sale desktop: same fan cascade, but every card is the same size
// (228.76 x 366.14) and only rotates/offsets - no receding. Mobile keeps its
// own separate 2-col x 2-row grid (mountFlashSaleMobile above).
function mountFlashSaleDesktop() {
  mountFanCascadeCarousel("flashDesktopWrapper", SQV4_FLASH_POSITIONS_DESKTOP, {
    baseWidth: 228.76,
    baseHeight: 366.14,
  });
}

function initCarouselsV4() {
  safeMount("mountNewIn", mountNewIn);
  safeMount("mountFlashSaleMobile", mountFlashSaleMobile);
  safeMount("mountFlashSaleDesktop", mountFlashSaleDesktop);
  safeMount("mountBestSeller", mountBestSeller);
  safeMount("mountShopCategory", mountShopCategory);
  safeMount("mountTrustBar", mountTrustBar);
  safeMount("mountTrending", mountTrending);

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
