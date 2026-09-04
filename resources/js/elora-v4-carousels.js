// Swiper wiring for the ELORA v4 home screen carousels.
// Product card markup is rendered server-side in the Blade sections
// (resources/views/themes/elora/pages/home-v4/sections/*.blade.php and their
// partials/ subfolder), so this file only mounts Swiper on the existing DOM.

function mountNewIn() {
  const wrapper = document.getElementById("newInWrapper");
  if (!wrapper) return;
  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 1.75,
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

function mountTrending() {
  const wrapper = document.getElementById("trendingWrapper");
  if (!wrapper) return;
  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 2.5,
    spaceBetween: 16,
  });
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
