// Swiper wiring for the Souqify home screen (v3).
// Unlike the static mockup's carousels.js, the product cards in these wrappers are now
// server-rendered by Blade with real product data, so this file ONLY initializes Swiper
// on the existing DOM nodes — it no longer injects any hardcoded card HTML.

// One section's mount failing (e.g. missing DOM, a bad selector) must never
// silently skip every mount call that comes after it in initCarouselsV3().
function safeMount(name, fn) {
  try {
    fn();
  } catch (e) {
    console.error(`${name} failed`, e);
  }
}

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

// Best Seller uses a draggable fan cascade — same technique as Green Edition
// (elora-v5-carousels.js's mountBestSellerCarousel). Geometry is captured
// from Souqify's own Figma card stack, indexed by DISTANCE from the
// swiper's activeIndex (0 = the "front" slot ... down to the last), not by
// a fixed product index. Every slide is absolutely positioned from a table
// each time activeIndex changes, so dragging shifts which product lands in
// the distance-0 slot while the rest cascade around it: the front card is
// biggest and the rest recede in size behind it.
const SQV3_CASCADE_POSITIONS_DESKTOP = [
  { left: 0, top: 0, width: 295.45, height: 472.89 },
  { left: 273, top: 9, width: 283.96, height: 454.2 },
  { left: 524.18, top: 31, width: 255.24, height: 409 },
  { left: 755, top: 57, width: 229.29, height: 367 },
  { left: 965, top: 75, width: 210.06, height: 336 },
  { left: 1162, top: 95, width: 192.21, height: 308 },
];
const SQV3_CASCADE_POSITIONS_MOBILE = [
  { left: 0, top: 0, width: 154.5, height: 223.1 },
  { left: 120.75, top: 13.39, width: 141, height: 203.02 },
  { left: 248.25, top: 26.77, width: 127.5, height: 182.94 },
];
const SQV3_CASCADE_BASE_WIDTH_DESKTOP = 295.45;
const SQV3_CASCADE_BASE_WIDTH_MOBILE = 154.5;

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
      // Every slide is the SAME base size (a .sqv3-*__deal-base class),
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

function mountBestSeller() {
  // Guarded independently — a failure mounting one breakpoint's instance
  // must never prevent the other from mounting.
  try {
    mountFanCascadeCarousel("bestSellerMobileWrapper", SQV3_CASCADE_POSITIONS_MOBILE, {
      baseWidth: SQV3_CASCADE_BASE_WIDTH_MOBILE,
      dotsContainerId: "bestSellerMobileDots",
    });
  } catch (e) {
    console.error("mountBestSeller (mobile) failed", e);
  }
  try {
    mountFanCascadeCarousel("bestSellerDesktopWrapper", SQV3_CASCADE_POSITIONS_DESKTOP, {
      baseWidth: SQV3_CASCADE_BASE_WIDTH_DESKTOP,
      dotsContainerId: "bestSellerDesktopDots",
    });
  } catch (e) {
    console.error("mountBestSeller (desktop) failed", e);
  }
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
  safeMount("mountTrustBar", mountTrustBar);
  safeMount("mountFlash", mountFlash);
  safeMount("mountNewIn", mountNewIn);
  safeMount("mountTrending", mountTrending);
  safeMount("mountCategories", mountCategories);
  safeMount("mountShopCategory", mountShopCategory);
  safeMount("mountBestSeller", mountBestSeller);

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
