// Swiper wiring for the ELORA "v6 — New In" home screen carousels.
// Product card markup is rendered server-side in the Blade sections
// (resources/views/themes/elora/pages/home-v6/sections/*.blade.php and their
// partials/ subfolder), so this file only mounts Swiper instances + the
// flash-sale countdown, mirroring resources/js/elora-v2-carousels.js.

function mountCarousel(wrapperId) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
  });
}

function mountTrending() {
  const wrapper = document.getElementById("trendingWrapper");
  if (!wrapper) return;

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 2.5,
    spaceBetween: 16,
    slidesOffsetAfter: 16,
  });
}

function mountFlashGrid() {
  const grid = document.getElementById("flashGrid");
  if (!grid) return;

  return new Swiper(grid.closest(".swiper"), {
    slidesPerView: 3,
    spaceBetween: 12,
    grid: { rows: 3, fill: "row" },
  });
}

function mountBestSeller() {
  const wrapper = document.getElementById("bestSellerWrapper");
  if (!wrapper) return;

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 1.75,
    spaceBetween: 16,
    navigation: { prevEl: "#bestSellerPrev", nextEl: "#bestSellerNext" },
  });
}

function initCarousels() {
  mountCarousel("newInWrapper");
  mountTrending();
  mountFlashGrid();
  mountBestSeller();

  // Flash-sale countdown (starts from the design's captured 03:06:25 and ticks down)
  let remaining = 3 * 3600 + 6 * 60 + 25;
  const timerElH = document.getElementById("flashTimerH");
  const timerElM = document.getElementById("flashTimerM");
  const timerElS = document.getElementById("flashTimerS");
  if (timerElH && timerElM && timerElS) {
    setInterval(() => {
      remaining = Math.max(0, remaining - 1);
      const h = String(Math.floor(remaining / 3600)).padStart(2, "0");
      const m = String(Math.floor((remaining % 3600) / 60)).padStart(2, "0");
      const s = String(remaining % 60).padStart(2, "0");
      timerElH.textContent = h;
      timerElM.textContent = m;
      timerElS.textContent = s;
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
