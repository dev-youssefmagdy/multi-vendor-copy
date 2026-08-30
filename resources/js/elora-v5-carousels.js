// Swiper wiring + Best Seller fan-cascade positioning for the ELORA "Home" screen (Theme #2).
// Product card markup is rendered server-side in the Blade sections
// (resources/views/themes/elora/pages/home-v5/sections/*.blade.php and their
// partials/ subfolder), so this file only mounts Swiper on the existing DOM
// and drives the interactive bits that can't be expressed as static markup.

function mountFlashSale() {
  const wrapper = document.getElementById("flashSaleWrapper");
  if (!wrapper) return;
  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    navigation: { prevEl: "#flashSalePrev", nextEl: "#flashSaleNext" },
  });
}

// New In: every slide has a fixed !w-[Npx] of its own set in the Blade
// markup, so slidesPerView must stay "auto" — a numeric value would compute
// a pixel width that the fixed class permanently overrides, letting the
// wrapper's real content width run past the container and overflow the page.
function mountNewIn() {
  const wrapper = document.getElementById("newInWrapper");
  if (!wrapper) return;
  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    slidesOffsetAfter: 16,
  });
}

// Trending Now: same wide-card design as New In, but as a 2-row Swiper Grid
// carousel showing 2.5 columns per view. Grid needs a numeric slidesPerView
// (columns) — unlike New In's slides, these have no fixed !w-[Npx].
function mountTrending() {
  const wrapper = document.getElementById("trendingWrapper");
  if (!wrapper) return;
  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 2.5,
    spaceBetween: 16,
    grid: { rows: 2, fill: "row" },
    slidesOffsetAfter: 16,
  });
}

// Best Seller: draggable fan cascade — geometry captured from the Figma card
// stack (node 2007:7743 / 2007:5559), indexed by DISTANCE from the swiper's
// activeIndex (0 = leftmost/biggest ... down to smallest/rightmost), not by
// a fixed product index. Every slide is absolutely positioned from this
// table each time activeIndex changes, so dragging shifts which product
// lands in the "distance 0" (biggest) slot while the rest cascade down —
// matching the original static fan's exact pixel geometry, but now live.
const BEST_SELLER_POSITIONS_DESKTOP = [
  { left: 0, top: 0, width: 284, height: 430 },
  { left: 230, top: 39, width: 256, height: 388 },
  { left: 440, top: 57, width: 244, height: 370 },
  { left: 630, top: 79, width: 230, height: 348 },
  { left: 811, top: 102, width: 215, height: 326 },
  { left: 980, top: 123, width: 203, height: 307 },
];
const BEST_SELLER_POSITIONS_MOBILE = [
  { left: 0, top: 0, width: 172, height: 262 },
  { left: 130, top: 35, width: 141, height: 213 },
  { left: 224, top: 58, width: 119, height: 180 },
];

function mountBestSellerCarousel(containerId, positions) {
  const container = document.getElementById(containerId);
  if (!container) return;
  container.style.position = "relative";
  container.style.height = `${positions[0].top + positions[0].height}px`;

  // Only the ORIGINAL slides (captured before Swiper's loop mode injects its
  // own clone slides) get positioned here — clones are neutralized separately
  // below, since our own modulo wrap already makes the cascade infinite
  // without needing Swiper's duplicated DOM nodes to be visually meaningful.
  const slideEls = [...container.children];
  const count = slideEls.length;
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
      slideEl.style.left = `${box.left}px`;
      slideEl.style.top = `${box.top}px`;
      slideEl.style.width = `${box.width}px`;
      slideEl.style.height = `${box.height}px`;
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
      init: applyPositions,
      slideChange: applyPositions,
      transitionEnd: applyPositions,
      setTransition(sw, duration) {
        slideEls.forEach((s) => (s.style.transitionDuration = `${duration}ms`));
      },
    },
  });
}

function initCarousels() {
  mountFlashSale();
  mountNewIn();
  mountTrending();
  mountBestSellerCarousel("bestSellerMobileWrapper", BEST_SELLER_POSITIONS_MOBILE);
  mountBestSellerCarousel("bestSellerDesktopWrapper", BEST_SELLER_POSITIONS_DESKTOP);

  // Flash-sale countdown (starts from the design's captured 03:06:25 and ticks down)
  let remaining = 3 * 3600 + 6 * 60 + 25;
  const timerEls = document.querySelectorAll("[data-flash-timer]");
  if (timerEls.length) {
    setInterval(() => {
      remaining = Math.max(0, remaining - 1);
      const h = String(Math.floor(remaining / 3600)).padStart(2, "0");
      const m = String(Math.floor((remaining % 3600) / 60)).padStart(2, "0");
      const s = String(remaining % 60).padStart(2, "0");
      timerEls.forEach((el, i) => {
        el.textContent = [h, m, s][i];
      });
    }, 1000);
  }

  // Mobile feature strip — cycles through the pre-rendered feature items one at a
  // time (matches the design's mobile layout, which shows a single feature versus
  // the desktop's full four-item row) by swapping each item's flex/hidden class.
  const featureItems = document.querySelectorAll(
    "#featureStripMobile .feature-strip-item",
  );
  if (featureItems.length) {
    let featureIndex = 0;
    setInterval(() => {
      featureItems[featureIndex].classList.remove("flex");
      featureItems[featureIndex].classList.add("hidden");
      featureIndex = (featureIndex + 1) % featureItems.length;
      featureItems[featureIndex].classList.remove("hidden");
      featureItems[featureIndex].classList.add("flex");
    }, 3000);
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
