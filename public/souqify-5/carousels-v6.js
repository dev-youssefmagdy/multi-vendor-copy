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
    // Phones show exactly four tiles, one view per swipe.
    slidesPerView: 4,
    slidesPerGroup: 4,
    spaceBetween: 12,
    breakpoints: {
      640: { slidesPerView: 5, slidesPerGroup: 5, spaceBetween: 16 },
      // The comp's 1347px row holds all eight tiles at a 24px gap. The blade
      // now renders up to 20 categories (see browse_categories.blade.php),
      // so stores with more than 8 still have real overflow to drag through
      // - only a store with 8 or fewer categories won't need to scroll here,
      // which is correct (nothing to scroll to).
      1024: { slidesPerView: 8, slidesPerGroup: 8, spaceBetween: 24 },
    },
  });
}

// Flash Sale: draggable fan cascade — ported mechanism-for-mechanism from
// Elora Minimal Edition's Best Seller carousel (resources/js/elora-v5-carousels.js,
// mountBestSellerCarousel): a real Swiper instance drives touch/drag with
// explicit `loop: true` + `loopedSlides` (so loop mode never falls back to
// the broken clone-count guess it makes for a short slide set — that guess
// is what caused the old fan cascade here to get stuck after one swipe),
// while every slide is absolutely positioned from a fixed geometry table
// indexed by DISTANCE from the swiper's activeIndex (0 = biggest/frontmost
// ... down to smallest), so dragging shifts which product lands in the
// front slot while the rest cascade down in size behind it.
const SQV6_FLASH_POSITIONS_DESKTOP = [
  { left: 0, top: 0, width: 295.45, height: 472.89 },
  { left: 273, top: 9, width: 283.96, height: 454.2 },
  { left: 524.18, top: 31, width: 255.24, height: 409 },
  { left: 755, top: 57, width: 229.29, height: 367 },
  { left: 965, top: 75, width: 210.06, height: 336 },
  { left: 1162, top: 95, width: 192.21, height: 308 },
];
const SQV6_FLASH_POSITIONS_MOBILE = [
  { left: 0, top: 0, width: 154.5, height: 223.1 },
  { left: 120.75, top: 13.39, width: 141, height: 203.02 },
  { left: 248.25, top: 26.77, width: 127.5, height: 182.94 },
];

// RTL: the geometry table is captured left-to-right (distance 0 pinned off
// the start edge). Under RTL the biggest/active card keeps that same gap but
// from the right edge instead, so every box's `left` is mirrored around the
// container's actual rendered width (matches Elora's mirrorPositionsRtl()).
function sqv6MirrorPositionsRtl(positions, containerWidth) {
  return positions.map((p) => ({ ...p, left: containerWidth - p.left - p.width }));
}

function mountFlashCascade(containerId, positions, { baseWidth, dotsContainerId = null } = {}) {
  const container = document.getElementById(containerId);
  if (!container) return;
  const isRtl = document.documentElement.getAttribute("dir") === "rtl";
  const boxes = isRtl ? sqv6MirrorPositionsRtl(positions, container.clientWidth) : positions;
  container.style.position = "relative";
  container.style.height = `${boxes[0].top + boxes[0].height}px`;

  const slideEls = [...container.children];
  const count = slideEls.length;

  const dotsEl = dotsContainerId ? document.getElementById(dotsContainerId) : null;
  if (dotsEl) {
    dotsEl.innerHTML = "";
    for (let i = 0; i < count; i++) {
      const dot = document.createElement("button");
      dot.type = "button";
      dot.className = "sqv6-flash-dot" + (i === 0 ? " is-active" : "");
      dot.setAttribute("aria-label", `Product ${i + 1}`);
      dotsEl.appendChild(dot);
    }
  }
  const updateDots = (sw) => {
    if (!dotsEl) return;
    dotsEl.querySelectorAll(".sqv6-flash-dot").forEach((dot, i) => {
      dot.classList.toggle("is-active", i === sw.realIndex);
    });
  };

  const applyPositions = (sw) => {
    slideEls.forEach((slideEl, i) => {
      const distance = (((i - sw.realIndex) % count) + count) % count;
      const box = boxes[distance];
      if (!box) {
        slideEl.style.opacity = "0";
        slideEl.style.pointerEvents = "none";
        slideEl.style.zIndex = "0";
        return;
      }
      slideEl.style.opacity = "1";
      slideEl.style.pointerEvents = "";
      const scale = box.width / baseWidth;
      slideEl.style.transform = `translate(${box.left}px, ${box.top}px) scale(${scale})`;
      slideEl.style.zIndex = String(boxes.length - distance);
    });
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

function mountFlash() {
  mountFlashCascade("flashMobileWrapper", SQV6_FLASH_POSITIONS_MOBILE, {
    baseWidth: 154.5,
  });
  mountFlashCascade("flashDesktopWrapper", SQV6_FLASH_POSITIONS_DESKTOP, {
    baseWidth: 295.45,
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
    // A swipe advances one tile at a time rather than paging to the next
    // group of three/four/six.
    slidesPerView: 3,
    slidesPerGroup: 1,
    spaceBetween: 12,
    breakpoints: {
      640: { slidesPerView: 4, slidesPerGroup: 1, spaceBetween: 16 },
      // The comp's 1328px row holds all six tiles at a 16px gap. The blade
      // now renders up to 20 categories (see explore_products.blade.php), so
      // stores with more than 6 still have real overflow to drag through -
      // only a store with 6 or fewer categories won't need to scroll here,
      // which is correct (nothing to scroll to).
      1024: { slidesPerView: 6, slidesPerGroup: 1, spaceBetween: 16 },
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
    // The comp's 1328px row holds exactly five cards at a 21px gap, but a
    // swipe now advances one card at a time rather than the whole page.
    slidesPerView: 5,
    slidesPerGroup: 1,
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
