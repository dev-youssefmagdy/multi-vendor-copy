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
      // Exactly 8 categories are rendered server-side (categories.take(8)),
      // so a numeric slidesPerView here shows all of them at once on
      // desktop with no swiping needed — each slide's width is Swiper's own
      // computed 1/8th share of the container, not the mobile fixed px.
      1024: { slidesPerView: 8, spaceBetween: 32 },
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

// Best Seller on mobile: a real swipeable carousel, 2 columns x 2 rows per
// page (Swiper's grid module), instead of the desktop's single-row fanned
// stack — matches the reference design's "2 rows, 2 cards per row" mobile
// layout while still letting you swipe to further products.
function mountBestSellerMobile() {
  const wrapper = document.getElementById("bestSellerMobileWrapper");
  if (!wrapper) return;
  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 2,
    slidesPerGroup: 2,
    spaceBetween: 24,
    grid: { rows: 2, fill: "row" },
  });
}

// Shop by Category: mobile pages 2 columns x 2 rows (same grid-module
// technique as Best Seller mobile above), desktop switches to a plain
// single-row carousel showing 6 slides per view.
function mountShopByCategory() {
  const wrapper = document.getElementById("shopByCategoryWrapper");
  if (!wrapper) return;
  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 2,
    slidesPerGroup: 2,
    spaceBetween: 8,
    grid: { rows: 2, fill: "row" },
    breakpoints: {
      1024: {
        slidesPerView: 6,
        slidesPerGroup: 6,
        spaceBetween: 24,
        grid: { rows: 1, fill: "row" },
      },
    },
  });
}

function initCarousels() {
  mountNewIn();
  mountCategories();
  mountTrending();
  mountFlashSale();
  mountBestSeller();
  mountBestSellerMobile();
  mountShopByCategory();
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
