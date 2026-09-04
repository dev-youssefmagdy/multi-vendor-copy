// Swiper wiring + flash-sale countdown for the ELORA "Theme #4" home screen.
// Product card markup lives entirely in the Blade sections/partials now — this
// file only turns the pre-rendered slides into working carousels.

function initCarousels() {
  // Trending Now: real Swiper carousel at both breakpoints (1.5 slides per
  // view mobile, 5.25 desktop) via Swiper's own `breakpoints` option — a
  // single persistent instance, unlike Flash Sale's create/destroy dance,
  // since both breakpoints here want a real carousel (just a different
  // slidesPerView), which is Swiper's well-tested standard use case.
  const trendingSwiperEl = document.getElementById("trendingSwiper");
  if (trendingSwiperEl) {
    new Swiper(trendingSwiperEl, {
      slidesPerView: 1.5,
      spaceBetween: 16,
      breakpoints: {
        1024: { slidesPerView: 5.25 },
      },
    });
  }

  // Flash Sale: mobile is a static 2-column grid of the first 4 products
  // (CSS-driven, see .card-swiper rules in elora-v3.css — not a scrollable
  // carousel), desktop is a real Swiper at 4.5 slides per view advanced via
  // the single "next" arrow next to the countdown. Fully creating/destroying
  // the Swiper instance across the breakpoint (rather than toggling
  // `enabled`) avoids Swiper's known issue where slide sizing doesn't
  // reliably re-run after an `enabled` toggle.
  const flashSaleSwiperEl = document.getElementById("flashSaleSwiper");
  if (flashSaleSwiperEl) {
    const flashSaleMq = window.matchMedia("(min-width: 1024px)");
    let flashSaleInstance = null;
    const syncFlashSale = () => {
      if (flashSaleMq.matches && !flashSaleInstance) {
        flashSaleInstance = new Swiper(flashSaleSwiperEl, {
          slidesPerView: 4.5,
          spaceBetween: 16,
          navigation: { nextEl: "#flashSaleNext" },
        });
      } else if (!flashSaleMq.matches && flashSaleInstance) {
        flashSaleInstance.destroy(true, true);
        flashSaleInstance = null;
      }
    };
    syncFlashSale();
    flashSaleMq.addEventListener("change", syncFlashSale);
  }

  // Best Seller: mobile is a static 2-column grid (CSS-driven, see
  // .bestseller-swiper rules in elora-v3.css — not a scrollable carousel),
  // desktop is a real "auto"-width Swiper (cards keep their intrinsic
  // 309px width) with alternating cards sitting lower via CSS. Same
  // create/destroy-across-breakpoint approach as Flash Sale.
  const bestSellerSwiperEl = document.getElementById("bestSellerSwiper");
  if (bestSellerSwiperEl) {
    const bestSellerMq = window.matchMedia("(min-width: 1024px)");
    let bestSellerInstance = null;
    const syncBestSeller = () => {
      if (bestSellerMq.matches && !bestSellerInstance) {
        bestSellerInstance = new Swiper(bestSellerSwiperEl, {
          slidesPerView: "auto",
          spaceBetween: 32,
        });
      } else if (!bestSellerMq.matches && bestSellerInstance) {
        bestSellerInstance.destroy(true, true);
        bestSellerInstance = null;
      }
    };
    syncBestSeller();
    bestSellerMq.addEventListener("change", syncBestSeller);
  }

  // New In: real Swiper carousel using the Grid module — a fixed numeric
  // slidesPerView (columns visible) x grid rows:3, so each "slide" is really one
  // column and the view shows 3 stacked rows per column. Numeric slidesPerView
  // is required for the Grid module's row math to work at all (fractional/auto
  // slidesPerView leaves every slide with an identical, wrong row assignment).
  // Mobile is a single column (1 x 3 rows), desktop is 3 columns x 3 rows.
  const newInSwiper = document.getElementById("newInSwiper");
  if (newInSwiper) {
    new Swiper(newInSwiper, {
      slidesPerView: 1,
      spaceBetween: 16,
      grid: { rows: 3, fill: "row" },
      breakpoints: {
        1024: { slidesPerView: 3 },
      },
    });
  }

  // Shop by Category: real Swiper carousel so it can hold many records.
  const shopByCategorySwiper = document.getElementById("shopByCategorySwiper");
  if (shopByCategorySwiper) {
    new Swiper(shopByCategorySwiper, {
      slidesPerView: "auto",
      spaceBetween: 13,
      navigation: {
        prevEl: "#shopByCategoryPrev",
        nextEl: "#shopByCategoryNext",
      },
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
