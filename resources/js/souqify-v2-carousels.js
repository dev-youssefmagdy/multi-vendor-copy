// Swiper wiring for the Souqify home screen carousels (v2).
// Product card markup is rendered server-side in the Blade sections
// (resources/views/themes/souqify/pages/home-v2/sections/*.blade.php and their
// partials/ subfolder) instead of being generated here from static JS data.

function mountCarousel({ wrapperId, prevId, nextId, extra }) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;

  return new Swiper(
    wrapper.closest(".swiper"),
    Object.assign(
      {
        slidesPerView: "auto",
        spaceBetween: 16,
        navigation: {
          prevEl: prevId ? `#${prevId}` : undefined,
          nextEl: nextId ? `#${nextId}` : undefined,
        },
      },
      extra || {},
    ),
  );
}

function mountTrustBar() {
  const swiperEl = document.querySelector(".trust-swiper");
  if (!swiperEl) return;

  // The marquee is a desktop-only effect: mobile shows the features one at a
  // time and the user swipes between them, so autoplay stays off there.
  // Evaluated once at init - Swiper cannot toggle autoplay from a breakpoint.
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
      640: { slidesPerView: 2.5, spaceBetween: 32 },
      // 52.39px spaceBetween is the Figma gap between feature blocks.
      1024: { slidesPerView: 3.5, spaceBetween: 52.39 },
    },
  });
}

function initCarousels() {
  mountTrustBar();

  mountCarousel({
    wrapperId: "flashSaleWrapper",
    prevId: "flashSalePrev",
    nextId: "flashSaleNext",
  });

  // New In is a plain CSS grid (not a Swiper carousel); Prev/Next just scroll
  // the grid horizontally by one column.
  const newInGrid = document.getElementById("newInWrapper");
  if (newInGrid && newInGrid.firstElementChild) {
    const newInPrev = document.getElementById("newInPrev");
    const newInNext = document.getElementById("newInNext");
    const scrollNewIn = (dir) => {
      const colWidth = newInGrid.firstElementChild.getBoundingClientRect().width + 12;
      newInGrid.scrollBy({ left: dir * colWidth, behavior: "smooth" });
    };
    if (newInPrev) newInPrev.addEventListener("click", () => scrollNewIn(-1));
    if (newInNext) newInNext.addEventListener("click", () => scrollNewIn(1));
  }

  mountCarousel({
    wrapperId: "trendingWrapper",
    prevId: "trendingPrev",
    nextId: "trendingNext",
  });

  mountCarousel({
    wrapperId: "bestSellerWrapper",
    prevId: "bestSellerPrev",
    nextId: "bestSellerNext",
  });

  // Flash-sale countdown: uses the real end time (data-flash-end, unix seconds)
  // rendered server-side when an active flash sale exists, otherwise falls back
  // to the design's captured 03:06:25 seed.
  const timerEls = document.querySelectorAll("[data-flash-timer]");
  if (timerEls.length) {
    const root = document.querySelector("[data-flash-end]");
    const endTs = root ? parseInt(root.getAttribute("data-flash-end"), 10) : null;
    let remaining = endTs
      ? Math.max(0, endTs - Math.floor(Date.now() / 1000))
      : 3 * 3600 + 6 * 60 + 25;

    setInterval(() => {
      remaining = Math.max(0, remaining - 1);
      const h = String(Math.floor(remaining / 3600)).padStart(2, "0");
      const m = String(Math.floor((remaining % 3600) / 60)).padStart(2, "0");
      const s = String(remaining % 60).padStart(2, "0");
      timerEls.forEach((el) => {
        const which = el.getAttribute("data-flash-timer");
        el.textContent = which === "h" ? h : which === "m" ? m : s;
      });
    }, 1000);
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
