// Swiper wiring for the ELORA v4 home screen carousels.
// Product card markup is rendered server-side in the Blade sections
// (resources/views/themes/elora/pages/home-v4/sections/*.blade.php and their
// partials/ subfolder), so this file only mounts Swiper on the existing DOM.

function mountNewIn() {
  const wrapper = document.getElementById("newInWrapper");
  if (!wrapper) return;
  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 0.5,
    spaceBetween: 16,
    slidesOffsetAfter: 16,
    breakpoints: {
      1024: {
        slidesPerView: "auto",
        slidesOffsetAfter: 56,
      },
    },
  });
}

function mountCategories() {
  const wrapper = document.getElementById("categoriesWrapper");
  if (!wrapper) return;
  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    breakpoints: {
      1024: { spaceBetween: 32 },
    },
  });
}

function mountTrending() {
  const wrapper = document.getElementById("trendingWrapper");
  if (!wrapper) return;
  const container = wrapper.closest(".swiper");
  // Mobile renders as a plain stacked column (no Swiper at all — toggling
  // Swiper's `enabled` option across a breakpoint doesn't reliably
  // re-run slide sizing/positioning), desktop is a real 2.5-per-view swiper.
  const mq = window.matchMedia("(min-width: 1024px)");
  let instance = null;

  function sync() {
    if (mq.matches && !instance) {
      instance = new Swiper(container, {
        slidesPerView: 2.5,
        spaceBetween: 16,
      });
    } else if (!mq.matches && instance) {
      instance.destroy(true, true);
      instance = null;
    }
  }

  sync();
  mq.addEventListener("change", sync);
}

function mountFlashSale() {
  const wrapper = document.getElementById("flashSaleWrapper");
  if (!wrapper) return;
  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 1,
    spaceBetween: 16,
    grid: { rows: 3, fill: "row" },
    breakpoints: {
      1024: { slidesPerView: 2 },
    },
  });
}

function mountBestSeller() {
  const wrapper = document.getElementById("bestSellerWrapper");
  if (!wrapper) return;
  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    navigation: {
      prevEl: "#bestSellerPrev",
      nextEl: "#bestSellerNext",
    },
  });
}

function initCarousels() {
  mountNewIn();
  mountCategories();
  mountTrending();
  mountFlashSale();
  mountBestSeller();
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
