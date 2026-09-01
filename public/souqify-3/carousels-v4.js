// Swiper wiring for the Souqify home screen (v4).
// The product cards in these wrappers are server-rendered by Blade with real product
// data, so this file ONLY initializes Swiper on the existing DOM nodes — it does not
// inject any hardcoded card HTML.

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

function mountBestSeller() {
  const wrapper = document.getElementById("bestSellerWrapper");
  if (!wrapper) return;
  const swiperEl = wrapper.closest(".swiper");
  if (!swiperEl) return;

  new Swiper(swiperEl, {
    slidesPerView: "auto",
    spaceBetween: 16,
    loop: false,
    autoplay: { delay: 3500, disableOnInteraction: false },
    pagination: {
      el: ".best-seller-pagination",
      clickable: true,
      bulletClass: "best-seller-dot",
      bulletActiveClass: "is-active",
    },
  });
}

function initCarouselsV4() {
  mountSwiper("newInWrapper", "newInPrev", "newInNext");
  mountBestSeller();

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
