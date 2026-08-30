// Swiper wiring for the ELORA v3 home screen carousels.
// Product card markup is rendered server-side in the Blade sections
// (resources/views/themes/elora/pages/home-v3/sections/*.blade.php and their
// partials/ subfolder) instead of being generated here from JS data.

function mountFlashSale() {
  const wrapper = document.querySelector(".pattern-flash-sale .swiper-wrapper");
  if (!wrapper) return;

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    navigation: { prevEl: "#flashSalePrev", nextEl: "#flashSaleNext" },
  });
}

function mountNewIn() {
  const wrapper = document.querySelector(".newin-swiper .swiper-wrapper");
  if (!wrapper) return;

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 3,
    spaceBetween: 16,
    grid: { rows: 3, fill: "row" },
  });
}

function mountTrending() {
  const prevBtn = document.getElementById("trendingPrev");
  const wrapper = prevBtn?.parentElement?.querySelector(".swiper-wrapper");
  if (!wrapper) return;

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    navigation: { prevEl: "#trendingPrev", nextEl: "#trendingNext" },
  });
}

function mountBestSeller() {
  const wrapper = document.querySelector(".bestseller-swiper .swiper-wrapper");
  if (!wrapper) return;

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    navigation: { prevEl: "#bestSellerPrev", nextEl: "#bestSellerNext" },
  });
}

function initCarousels() {
  mountFlashSale();
  mountNewIn();
  mountTrending();
  mountBestSeller();

  // Flash-sale countdown (starts from the design's captured 03:06:25 and ticks down)
  let remaining = 3 * 3600 + 6 * 60 + 25;
  const hEl = document.getElementById("flashHours");
  const mEl = document.getElementById("flashMinutes");
  const sEl = document.getElementById("flashSeconds");
  if (hEl && mEl && sEl) {
    setInterval(() => {
      remaining = Math.max(0, remaining - 1);
      hEl.textContent = String(Math.floor(remaining / 3600)).padStart(2, "0");
      mEl.textContent = String(Math.floor((remaining % 3600) / 60)).padStart(2, "0");
      sEl.textContent = String(remaining % 60).padStart(2, "0");
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
