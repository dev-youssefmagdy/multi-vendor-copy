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
      // The comp's 1347px row holds all eight tiles at a 24px gap. The blade
      // now renders up to 20 categories (see browse_categories.blade.php),
      // so stores with more than 8 still have real overflow to drag through
      // - only a store with 8 or fewer categories won't need to scroll here,
      // which is correct (nothing to scroll to).
      1024: { slidesPerView: 8, slidesPerGroup: 8, spaceBetween: 24 },
    },
  });
}

// Flash Sale uses a draggable fan cascade - same technique as Green
// Edition's Best Seller carousel (souqify-3/carousels-v4.js's
// mountFanCascadeCarousel). Geometry is indexed by DISTANCE from the
// swiper's activeIndex (0 = the "front" slot ... down to the last), not by
// a fixed product index. Every slide is absolutely positioned from a table
// each time activeIndex changes, so dragging shifts which product lands in
// the distance-0 slot while the rest cascade down in size behind it - the
// front card cycles to the back as you page through.
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

function mountFanCascadeCarousel(containerId, positions, { baseWidth, baseHeight = null, dotsContainerId = null } = {}) {
  const container = document.getElementById(containerId);
  if (!container) return;
  container.style.position = "relative";
  container.style.height = `${Math.max(...positions.map((p) => p.top + (p.height ?? baseHeight)))}px`;

  // Only the ORIGINAL slides (captured before Swiper's loop mode injects its
  // own clone slides) get positioned here - clones are neutralized separately
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
      const scale = box.width != null ? box.width / baseWidth : 1;
      const rotate = box.rot ? ` rotate(${box.rot}deg)` : "";
      slideEl.style.transform = `translate(${box.left}px, ${box.top}px)${rotate} scale(${scale})`;
      slideEl.style.zIndex = String(positions.length - distance);
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
    // Swiper's own loop mode needs enough duplicated slides to cover
    // slidesPerView on both sides of the real ones; with slidesPerView:"auto"
    // and as few as 3 real slides (mobile), count alone isn't enough - Swiper
    // warns "not enough slides for loop mode" and the first paint can render
    // slides outside their cascade position until a swipe forces a relayout.
    // Our own applyPositions() already hides every .swiper-slide-duplicate,
    // so asking for extra loop clones here is free - it just satisfies
    // Swiper's internal loop bookkeeping.
    loopedSlides: count * 4,
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
      // Without this, resizing the viewport (rotating a phone, resizing a
      // desktop window, or a DevTools device-toolbar resize without a full
      // reload) leaves the translate() offsets computed for the OLD width
      // while the box's own CSS width/height already shrank via the media
      // query - cards fly outside the frame instead of rescaling with it.
      resize: applyPositions,
      setTransition(sw, duration) {
        slideEls.forEach((s) => (s.style.transitionDuration = `${duration}ms`));
      },
    },
  });
}

function mountFlash() {
  try {
    mountFanCascadeCarousel("flashMobileWrapper", SQV6_FLASH_POSITIONS_MOBILE, {
      baseWidth: 154.5,
    });
  } catch (e) {
    console.error("mountFlash (mobile) failed", e);
  }
  try {
    mountFanCascadeCarousel("flashDesktopWrapper", SQV6_FLASH_POSITIONS_DESKTOP, {
      baseWidth: 295.45,
    });
  } catch (e) {
    console.error("mountFlash (desktop) failed", e);
  }
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
      // The comp's 1328px row holds all six tiles at a 16px gap. The blade
      // now renders up to 20 categories (see explore_products.blade.php), so
      // stores with more than 6 still have real overflow to drag through -
      // only a store with 6 or fewer categories won't need to scroll here,
      // which is correct (nothing to scroll to).
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
    // The CSS stagger (see .sqv6-best--grid rules) needs to know which
    // physical column each card lands in, but with slidesPerGroup > 1
    // Swiper's grid module assigns columns via a per-group formula (see
    // swiper's grid.mjs updateSlide()) rather than simple DOM order, so a
    // plain nth-child(2n) selector doesn't reliably track "first vs second
    // column" once there's more than one page of cards - some row-pairs
    // ended up with both cards on the same side of that parity by accident,
    // which is why they rendered level. Swiper stores the column it computed
    // as `.column` directly on each slide element, so reading that back is
    // the only reliable way to know which column a card is actually in.
    const applyStagger = () => {
      wrapper.querySelectorAll(".swiper-slide").forEach((slideEl) => {
        slideEl.classList.toggle("sqv6-best__slide--stagger", slideEl.column % 2 === 0);
      });
    };
    new Swiper(swiperEl, {
      slidesPerView: 2,
      slidesPerGroup: 2,
      spaceBetween: 12,
      grid: { rows: 2, fill: "row" },
      on: {
        init: applyStagger,
        update: applyStagger,
        resize: applyStagger,
      },
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
