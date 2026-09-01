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

function initCarouselsV5() {
  mountSwiper("newInWrapper", "newInPrev", "newInNext");
  mountSwiper("trendingWrapper", "trendingPrev", "trendingNext");
  mountSwiper("bestSellerWrapper", "bestSellerPrev", "bestSellerNext");

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
