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
      640: { slidesPerView: 6, spaceBetween: 16 },
      // The comp's 1347px row fits 8 whole tiles, but showing EXACTLY 8 left
      // nothing to drag whenever a store has 8 or fewer categories (the row
      // always fit perfectly). The .5 guarantees a sliver of the next tile
      // peeks in, so the row always reads as swipeable on desktop too.
      1024: { slidesPerView: 7.5, spaceBetween: 24 },
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
      640: { slidesPerView: 5, spaceBetween: 16 },
      // The comp's 1328px row fits 7 whole tiles, but showing EXACTLY 7 left
      // nothing to drag whenever a store has 7 or fewer categories (the row
      // always fit perfectly). The .5 guarantees a sliver of the next tile
      // peeks in, so the row always reads as swipeable on desktop too.
      1024: { slidesPerView: 6.5, spaceBetween: 25.96 },
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

// Best Seller (desktop) uses a draggable ROTATED fan cascade - same
// scattered-card-stack technique as Green Edition's Flash Sale carousel
// (souqify-3/carousels-v4.js's SQV4_FLASH_POSITIONS_DESKTOP fed through
// mountFanCascadeCarousel). Geometry is indexed by DISTANCE from the
// swiper's activeIndex (0 = the "front" slot ... down to the last), not by
// a fixed product index. Every slide is absolutely positioned from a table
// each time activeIndex changes, so dragging shifts which product lands in
// the distance-0 slot while the rest cascade around it. Every card stays
// the same size, just rotated/offset (a `rot` per slot and no `width` -
// mountFanCascadeCarousel leaves scale at 1 when a slot has no width).
// This table is Green Edition's own Flash Sale table scaled up by
// 295.45/228.76 (this section's base card width over Flash Sale's), then
// normalized so the smallest top is 0.
const SQV5_CASCADE_POSITIONS_DESKTOP = [
  { left: 0, top: 43.32, rot: -11.98 },
  { left: 300.94, top: 0, rot: 0 },
  { left: 501.55, top: 44.63, rot: 8.97 },
  { left: 691.44, top: 20.02, rot: -11.98 },
  { left: 980.85, top: 11.03, rot: 0 },
  { left: 1153.98, top: 76.22, rot: 8.97 },
];
const SQV5_CASCADE_BASE_WIDTH_DESKTOP = 295.45;
const SQV5_CASCADE_BASE_HEIGHT_DESKTOP = 472.89;

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

// Best Seller (mobile) uses the same "scattered card stack" carousel as
// Green Edition's Flash Sale mobile carousel (souqify-3/carousels-v4.js's
// mountCardFanMobile): one centered card with its neighbors peeking out on
// either side, rotated. Every slide's CSS base position is centered
// directly (left: calc(50% - 77.25px), 77.25 = half the 154.5px card
// width), so with translateX(0) the active card sits exactly centered; this
// function then offsets each neighbor from that centered base by a fixed
// pixel amount via transform, independent of anything Swiper measures.
const SQV5_CARD_FAN_MOBILE_MAX_ROTATE_DEG = 12;
const SQV5_CARD_FAN_MOBILE_STEP_PX = 110;

function mountCardFanMobile(wrapperId, { dotsContainerId = null } = {}) {
  const container = document.getElementById(wrapperId);
  if (!container) return;
  const swiperEl = container.closest(".swiper");
  if (!swiperEl) return;

  container.style.position = "relative";

  // Only the ORIGINAL slides (captured before Swiper's loop mode injects
  // its own clones) get positioned here - clones are hidden separately
  // below, since our own modulo wrap already makes the cascade infinite
  // without needing Swiper's duplicated DOM nodes to be visually meaningful.
  const slideEls = [...container.children];
  const count = slideEls.length;
  if (!count) return;

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

  // Distance is each slide's own index minus the REAL (loop-aware) active
  // index, wrapped to the shortest signed path around the loop - so paging
  // past the last card correctly shows the first card as "next" (d=+1)
  // rather than some large positive number. Negative d (left neighbor)
  // rotates counter-clockwise, positive d (right neighbor) clockwise;
  // anything beyond the immediate neighbor is force-hidden so exactly 3
  // cards are ever visible.
  const applyLayout = (sw) => {
    slideEls.forEach((slideEl, i) => {
      let d = i - sw.realIndex;
      if (d > count / 2) d -= count;
      if (d < -count / 2) d += count;
      const dist = Math.min(Math.abs(d), 2);
      const scale = 1 - dist * 0.12;
      const rotate = Math.max(
        -SQV5_CARD_FAN_MOBILE_MAX_ROTATE_DEG,
        Math.min(SQV5_CARD_FAN_MOBILE_MAX_ROTATE_DEG, d * SQV5_CARD_FAN_MOBILE_MAX_ROTATE_DEG)
      );
      slideEl.style.transform = `translateX(${d * SQV5_CARD_FAN_MOBILE_STEP_PX}px) rotate(${rotate}deg) scale(${scale})`;
      slideEl.style.zIndex = String(10 - Math.round(dist * 4));
      slideEl.style.opacity = dist > 1 ? "0" : "1";
      slideEl.style.pointerEvents = dist > 1 ? "none" : "";
    });
    // Loop mode clones aren't part of slideEls and never get positioned -
    // hide them so they don't sit stacked at their default position.
    container.querySelectorAll(".swiper-slide-duplicate").forEach((clone) => {
      clone.style.opacity = "0";
      clone.style.pointerEvents = "none";
    });
  };

  return new Swiper(swiperEl, {
    slidesPerView: "auto",
    virtualTranslate: true,
    loop: true,
    // Swiper's own loop mode needs enough duplicated slides to cover
    // slidesPerView on both sides of the real ones; with slidesPerView:"auto"
    // and as few as 3 real slides (mobile), count alone isn't enough - Swiper
    // warns "not enough slides for loop mode" and the first paint can render
    // slides outside their intended position until a swipe forces a
    // relayout. The per-slide positioning code above already hides every
    // .swiper-slide-duplicate, so asking for extra loop clones is free.
    loopedSlides: count * 4,
    on: {
      init(sw) {
        applyLayout(sw);
        updateDots(sw);
      },
      slideChange(sw) {
        applyLayout(sw);
        updateDots(sw);
      },
      transitionEnd: applyLayout,
      resize: applyLayout,
      setTransition(sw, duration) {
        slideEls.forEach((s) => (s.style.transitionDuration = `${duration}ms`));
      },
    },
  });
}

function mountBestSeller() {
  // Guarded independently - a failure mounting one breakpoint's instance
  // must never prevent the other from mounting.
  try {
    mountCardFanMobile("bestSellerMobileWrapper", { dotsContainerId: "bestSellerMobileDots" });
  } catch (e) {
    console.error("mountBestSeller (mobile) failed", e);
  }
  try {
    mountFanCascadeCarousel("bestSellerDesktopWrapper", SQV5_CASCADE_POSITIONS_DESKTOP, {
      baseWidth: SQV5_CASCADE_BASE_WIDTH_DESKTOP,
      baseHeight: SQV5_CASCADE_BASE_HEIGHT_DESKTOP,
      dotsContainerId: "bestSellerDesktopDots",
    });
  } catch (e) {
    console.error("mountBestSeller (desktop) failed", e);
  }
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
