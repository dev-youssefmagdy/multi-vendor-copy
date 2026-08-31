// Swiper wiring + flash-sale countdown for the ELORA "Theme #4" home screen.
// Product card markup lives entirely in the Blade sections/partials now — this
// file only turns the pre-rendered slides into working carousels.

function initCarousels() {
  // Flash Sale, Trending Now, and Best Seller are plain horizontally
  // scrollable rows (like the Categories row) — no Swiper instance needed.

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

  // Flash-sale countdown: continue ticking down from whatever the server
  // already rendered (real sale end time), rather than a hardcoded value.
  const hEl = document.getElementById("flashHours");
  const mEl = document.getElementById("flashMinutes");
  const sEl = document.getElementById("flashSeconds");
  let remaining =
    hEl && mEl && sEl
      ? parseInt(hEl.textContent, 10) * 3600 +
        parseInt(mEl.textContent, 10) * 60 +
        parseInt(sEl.textContent, 10)
      : 0;
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
