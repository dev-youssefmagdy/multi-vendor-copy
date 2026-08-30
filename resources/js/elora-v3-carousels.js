// Swiper wiring + flash-sale countdown for the ELORA "Theme #4" home screen.
// Product card markup lives entirely in the Blade sections/partials now — this
// file only turns the pre-rendered slides into working carousels.

function initCarousels() {
  const flashSaleSwiper = document.getElementById("flashSaleSwiper");
  if (flashSaleSwiper) {
    new Swiper(flashSaleSwiper, {
      slidesPerView: "auto",
      spaceBetween: 16,
      navigation: {
        prevEl: "#flashSalePrev",
        nextEl: "#flashSaleNext",
      },
    });
  }

  // New In: real Swiper carousel using the Grid module — a fixed numeric
  // slidesPerView (columns visible) x grid rows:3, so each "slide" is really one
  // column and the view shows 3 stacked rows per column. Numeric slidesPerView
  // is required for the Grid module's row math to work at all (fractional/auto
  // slidesPerView leaves every slide with an identical, wrong row assignment).
  const newInSwiper = document.getElementById("newInSwiper");
  if (newInSwiper) {
    new Swiper(newInSwiper, {
      slidesPerView: 3,
      spaceBetween: 16,
      grid: { rows: 3, fill: "row" },
    });
  }

  const trendingSwiper = document.getElementById("trendingSwiper");
  if (trendingSwiper) {
    new Swiper(trendingSwiper, {
      slidesPerView: "auto",
      spaceBetween: 16,
      navigation: {
        prevEl: "#trendingPrev",
        nextEl: "#trendingNext",
      },
    });
  }

  const bestSellerSwiper = document.getElementById("bestSellerSwiper");
  if (bestSellerSwiper) {
    new Swiper(bestSellerSwiper, {
      slidesPerView: "auto",
      spaceBetween: 16,
      navigation: {
        prevEl: "#bestSellerPrev",
        nextEl: "#bestSellerNext",
      },
    });
  }

  // Flash-sale countdown (starts from the design's captured 03:06:25 and ticks down)
  let remaining = 3 * 3600 + 6 * 60 + 25;
  const hEl = document.getElementById("flashHours");
  const mEl = document.getElementById("flashMinutes");
  const sEl = document.getElementById("flashSeconds");
  if (hEl && mEl && sEl) {
    setInterval(() => {
      remaining = Math.max(0, remaining - 1);
      hEl.textContent = String(Math.floor(remaining / 3600)).padStart(2, "0");
      mEl.textContent = String(Math.floor((remaining % 3600) / 60)).padStart(
        2,
        "0",
      );
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
