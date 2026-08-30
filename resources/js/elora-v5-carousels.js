// Swiper wiring for the ELORA "Home v5" screen carousels.
// Product card markup is rendered server-side in the Blade sections
// (resources/views/themes/elora/pages/home-v5/sections/*.blade.php and their
// partials/ subfolder) — this file only mounts Swiper instances and, for the
// Best Seller fan cascade, positions the already-rendered slides.

function mountFlashSale() {
  const wrapper = document.getElementById("flashSaleWrapper");
  if (!wrapper) return;

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    navigation: { prevEl: "#flashSalePrev", nextEl: "#flashSaleNext" },
  });
}

function mountCarousel(wrapperId) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    slidesOffsetAfter: 16,
  });
}

// Trending Now: 2-row Swiper Grid carousel showing 2.5 columns per view.
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

// Best Seller: draggable fan cascade — geometry captured from the design's
// card stack, indexed by DISTANCE from the swiper's activeIndex (0 =
// leftmost/biggest ... down to smallest/rightmost), not by a fixed product
// index. Every slide is absolutely positioned from this table each time
// activeIndex changes, so dragging shifts which product lands in the
// "distance 0" (biggest) slot while the rest cascade down. Slides are
// already rendered server-side by the best_seller.blade.php section — this
// only positions them.
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

  const slideEls = [...container.children];
  const count = slideEls.length;
  if (!count) return;

  const applyPositions = (sw) => {
    slideEls.forEach((slideEl, i) => {
      const distance = (((i - sw.realIndex) % count) + count) % count;
      const box = positions[distance];
      if (!box) {
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
  mountCarousel("newInWrapper");
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

  // Mobile feature strip — single rotating feature (matches the design's
  // mobile layout, which shows one feature at a time versus the desktop's
  // full four-item row). The first feature is already server-rendered; this
  // just rotates through the rest.
  const featureStripMobile = document.getElementById("featureStripMobile");
  if (featureStripMobile) {
    const FEATURES = [
      { icon: "icon-feature-truck.svg", title: "Free Shipping", subtitle: "Free shipping on all your order" },
      { icon: "icon-feature-headphones.svg", title: "Customer Support 24/7", subtitle: "Instant access to Support" },
      { icon: "icon-feature-bag.svg", title: "100% Secure Payment", subtitle: "We ensure your money is save" },
      { icon: "icon-feature-package.svg", title: "Money-Back Guarantee", subtitle: "30 Days Money-Back Guarantee" },
    ];
    const iconBase = featureStripMobile.dataset.iconBase || "/elora-5/assets/icons/";
    let featureIndex = 0;
    const renderFeature = (f) => `
      <div class="flex items-center gap-[10px]">
        <img src="${iconBase}${f.icon}" alt="" class="size-[26px] shrink-0" />
        <div class="flex flex-col leading-tight">
          <span class="font-semibold text-[13px] text-white whitespace-nowrap">${f.title}</span>
          <span class="font-normal text-[11px] whitespace-nowrap" style="color:var(--color-stroke)">${f.subtitle}</span>
        </div>
      </div>`;
    setInterval(() => {
      featureIndex = (featureIndex + 1) % FEATURES.length;
      featureStripMobile.innerHTML = renderFeature(FEATURES[featureIndex]);
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
