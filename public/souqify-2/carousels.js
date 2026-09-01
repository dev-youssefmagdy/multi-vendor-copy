// Product card rendering + Swiper carousel wiring for the Souqify home screen.
// Card markup mirrors the Figma "Deal Card" component (node 2019:23464).
//
// PRODUCT IMAGE PLACEHOLDER RULE: per client instruction, product-card thumbnails in
// New In / Trending Now / Flash Sale / Recommended For You / Best Seller never load real
// product photography — renderProductCard() always renders an inline placeholder icon
// in the image slot instead of an <img>.

const PRODUCTS = {
  newIn: [
    { badge: "New", badgeBg: "var(--color-souqify-teal)", name: "SonicFlow Wireless Studio Headphones", weight: "200g", rating: "4.2 (+850)", price: "$149.00", oldPrice: "$199.00", discount: "50% off", discountBg: "var(--color-error)" },
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "AirBuds Pro Wireless Earbuds", weight: "45g", rating: "4.6 (+1.2k)", price: "$89.00", oldPrice: "$119.00", discount: "25% off", discountBg: "var(--color-souqify-orange)" },
    { badge: "Sale", badgeBg: "var(--color-accent-yellow)", name: "UltraView Tablet 11-inch", weight: "460g", rating: "4.4 (+640)", price: "$399.00", oldPrice: "$459.00", discount: "35% off", discountBg: "var(--color-accent-amber)" },
    { badge: "New", badgeBg: "var(--color-souqify-teal)", name: "NovaPhone X Smartphone", weight: "180g", rating: "4.7 (+2.1k)", price: "$699.00", oldPrice: null, discount: null, discountBg: null },
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "PixelSound Bluetooth Speaker", weight: "320g", rating: "4.3 (+510)", price: "$59.00", oldPrice: "$79.00", discount: "20% off", discountBg: "var(--color-error)" },
    { badge: "Sale", badgeBg: "var(--color-accent-yellow)", name: "AeroCam Mini Action Camera", weight: "150g", rating: "4.1 (+320)", price: "$129.00", oldPrice: "$169.00", discount: "25% off", discountBg: "var(--color-souqify-orange)" },
  ],
  trendingNow: [
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "SonicFlow Wireless Studio Headphones", weight: "200g", rating: "4.2 (+850)", price: "$149.00", oldPrice: "$199.00", discount: "50% off", discountBg: "var(--color-error)" },
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "NovaPhone X Smartphone", weight: "180g", rating: "4.7 (+2.1k)", price: "$699.00", oldPrice: "$799.00", discount: "13% off", discountBg: "var(--color-accent-amber)" },
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "AirBuds Pro Wireless Earbuds", weight: "45g", rating: "4.6 (+1.2k)", price: "$89.00", oldPrice: "$119.00", discount: "25% off", discountBg: "var(--color-souqify-orange)" },
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "UltraView Tablet 11-inch", weight: "460g", rating: "4.4 (+640)", price: "$399.00", oldPrice: null, discount: null, discountBg: null },
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "PixelSound Bluetooth Speaker", weight: "320g", rating: "4.3 (+510)", price: "$59.00", oldPrice: "$79.00", discount: "20% off", discountBg: "var(--color-error)" },
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "AeroCam Mini Action Camera", weight: "150g", rating: "4.1 (+320)", price: "$129.00", oldPrice: "$169.00", discount: "25% off", discountBg: "var(--color-souqify-orange)" },
  ],
  flashSale: [
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "SonicFlow Wireless Studio Headphones", weight: "200g", rating: "4.2 (+850)", price: "$149.00", oldPrice: "$199.00", discount: "35% off", discountBg: "var(--color-accent-amber)" },
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "AirBuds Pro Wireless Earbuds", weight: "45g", rating: "4.6 (+1.2k)", price: "$89.00", oldPrice: "$119.00", discount: "25% off", discountBg: "var(--color-souqify-orange)" },
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "NovaPhone X Smartphone", weight: "180g", rating: "4.7 (+2.1k)", price: "$699.00", oldPrice: "$799.00", discount: "13% off", discountBg: "var(--color-error)" },
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "UltraView Tablet 11-inch", weight: "460g", rating: "4.4 (+640)", price: "$399.00", oldPrice: "$459.00", discount: "13% off", discountBg: "var(--color-accent-amber)" },
  ],
  bestSeller: [
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "SonicFlow Wireless Studio Headphones", weight: "200g", rating: "4.2 (+850)", price: "$149.00", oldPrice: "$199.00", discount: "50% off", discountBg: "var(--color-error)" },
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "NovaPhone X Smartphone", weight: "180g", rating: "4.7 (+2.1k)", price: "$699.00", oldPrice: "$799.00", discount: "25% off", discountBg: "var(--color-souqify-orange)" },
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "AirBuds Pro Wireless Earbuds", weight: "45g", rating: "4.6 (+1.2k)", price: "$89.00", oldPrice: "$119.00", discount: "35% off", discountBg: "var(--color-accent-amber)" },
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "UltraView Tablet 11-inch", weight: "460g", rating: "4.4 (+640)", price: "$399.00", oldPrice: "$459.00", discount: "13% off", discountBg: "var(--color-error)" },
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "PixelSound Bluetooth Speaker", weight: "320g", rating: "4.3 (+510)", price: "$59.00", oldPrice: "$79.00", discount: "25% off", discountBg: "var(--color-souqify-orange)" },
  ],
  recommended: [
    { badge: "New", badgeBg: "var(--color-souqify-teal)", name: "SonicFlow Wireless Studio Headphones", weight: "200g", rating: "4.2 (+850)", price: "$149.00", oldPrice: "$199.00", discount: "50% off", discountBg: "var(--color-error)" },
    { badge: "Sale", badgeBg: "var(--color-accent-yellow)", name: "AirBuds Pro Wireless Earbuds", weight: "45g", rating: "4.6 (+1.2k)", price: "$89.00", oldPrice: "$119.00", discount: "25% off", discountBg: "var(--color-souqify-orange)" },
    { badge: "New", badgeBg: "var(--color-souqify-teal)", name: "UltraView Tablet 11-inch", weight: "460g", rating: "4.4 (+640)", price: "$399.00", oldPrice: "$459.00", discount: "13% off", discountBg: "var(--color-accent-amber)" },
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "NovaPhone X Smartphone", weight: "180g", rating: "4.7 (+2.1k)", price: "$699.00", oldPrice: null, discount: null, discountBg: null },
    { badge: "Sale", badgeBg: "var(--color-accent-yellow)", name: "PixelSound Bluetooth Speaker", weight: "320g", rating: "4.3 (+510)", price: "$59.00", oldPrice: "$79.00", discount: "20% off", discountBg: "var(--color-error)" },
    { badge: "New", badgeBg: "var(--color-souqify-teal)", name: "AeroCam Mini Action Camera", weight: "150g", rating: "4.1 (+320)", price: "$129.00", oldPrice: "$169.00", discount: "25% off", discountBg: "var(--color-souqify-orange)" },
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "GlowMate LED Desk Lamp", weight: "610g", rating: "4.0 (+210)", price: "$34.00", oldPrice: "$45.00", discount: "24% off", discountBg: "var(--color-accent-amber)" },
    { badge: "Sale", badgeBg: "var(--color-accent-yellow)", name: "TrailPack 20L Backpack", weight: "540g", rating: "4.5 (+980)", price: "$54.00", oldPrice: "$72.00", discount: "25% off", discountBg: "var(--color-souqify-orange)" },
    { badge: "New", badgeBg: "var(--color-souqify-teal)", name: "BrewMaster Smart Kettle", weight: "890g", rating: "4.3 (+430)", price: "$79.00", oldPrice: null, discount: null, discountBg: null },
    { badge: "Trending Now", badgeBg: "var(--color-souqify-teal)", name: "FlexFit Smart Watch", weight: "48g", rating: "4.6 (+1.5k)", price: "$159.00", oldPrice: "$199.00", discount: "20% off", discountBg: "var(--color-error)" },
  ],
};

// Simple inline "photo" placeholder icon used for every product-card image slot.
const PLACEHOLDER_ICON = `
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <rect x="3" y="4" width="18" height="16" rx="2" />
    <circle cx="8.5" cy="9.5" r="1.5" />
    <path d="M21 16l-5.5-5.5a2 2 0 0 0-2.8 0L3 20" />
  </svg>`;

function renderProductCard(p) {
  return `
    <div class="swiper-slide h-auto !w-[170px] lg:!w-[250px]">
      <div class="bg-[var(--color-bg-main)] flex flex-col items-start rounded-[8px] h-full shadow-sm overflow-hidden">
        <div class="relative shrink-0 w-full h-[160px] lg:h-[227px] product-image-placeholder">
          ${PLACEHOLDER_ICON}
          <span class="absolute top-0 left-0 flex items-center justify-center h-[26px] px-[10px] rounded-br-[10px] text-[11px] lg:text-[13px] font-medium text-white tracking-[0.3px] whitespace-nowrap" style="background:${p.badgeBg}">${p.badge}</span>
          <button type="button" aria-label="Add to favorites" class="absolute top-[8px] right-[8px] bg-white rounded-full p-[7px] shadow cursor-pointer">
            <img src="assets/icons/icon-heart.svg" alt="" class="size-[14px] lg:size-[16px]" />
          </button>
          <button type="button" aria-label="Quick view" class="absolute bottom-[8px] right-[8px] bg-white rounded-[6px] p-[7px] shadow cursor-pointer">
            <img src="assets/icons/icon-zoom.svg" alt="" class="size-[16px] lg:size-[18px]" />
          </button>
        </div>
        <div class="flex flex-col gap-[6px] p-[10px] lg:p-[12px] w-full flex-1">
          <div class="flex items-center justify-between gap-[6px]">
            <p class="font-medium text-[13px] lg:text-[16px] truncate" style="color:var(--color-card-title)">${p.name}</p>
            <p class="font-semibold text-[11px] lg:text-[13px] shrink-0" style="color:var(--color-primary)">${p.weight}</p>
          </div>
          <div class="flex items-center gap-[6px]">
            <img src="assets/icons/icon-star-rating.svg" alt="" class="h-[8px] w-[56px] lg:h-[10px] lg:w-[69px]" />
            <span class="text-[11px] lg:text-[12px] tracking-[0.3px]" style="color:var(--color-text-subtitle)">${p.rating}</span>
          </div>
          <div class="flex items-center justify-between gap-[6px]">
            <div class="flex items-end gap-[6px]">
              <p class="font-bold text-[15px] lg:text-[18px]" style="color:var(--color-souqify-teal)">${p.price}</p>
              ${p.oldPrice ? `<p class="text-[10px] lg:text-[12px] line-through" style="color:var(--color-gray)">${p.oldPrice}</p>` : ""}
            </div>
            ${p.discount ? `<span class="text-[9px] lg:text-[11px] font-normal text-white px-[6px] py-[3px] tracking-[0.3px] whitespace-nowrap" style="background:${p.discountBg}">${p.discount}</span>` : ""}
          </div>
          <div class="flex items-center gap-[4px] mt-auto pt-[2px]">
            <img src="assets/icons/icon-truck-small.svg" alt="" class="size-[14px] lg:size-[16px]" />
            <p class="text-[10px] lg:text-[12px] font-medium whitespace-nowrap" style="color:var(--color-success)">Delivered by 24 March</p>
          </div>
        </div>
      </div>
    </div>`;
}

// Flash Sale (desktop): big Deal Card slides interleaved with a "combo" slide
// bundling 3 stacked mini cards (node reference: Flash Sale carousel with a fixed
// vertical heading/countdown column on the left and a mixed big/mini card row).
function renderFlashMiniCard(p) {
  return `
    <div class="flex gap-0 h-[110px] items-stretch rounded-[8px] overflow-hidden" style="background:var(--color-bg-main)">
      <div class="relative shrink-0 w-[40%] product-image-placeholder">
        ${PLACEHOLDER_ICON}
        <span class="absolute top-0 left-0 flex items-center justify-center h-[20px] px-[7px] rounded-br-[8px] text-[9px] font-medium text-white tracking-[0.3px] whitespace-nowrap" style="background:${p.badgeBg}">${p.badge}</span>
        <button type="button" aria-label="Add to favorites" class="absolute top-[6px] right-[6px] bg-white rounded-full p-[5px] shadow cursor-pointer">
          <img src="assets/icons/icon-heart.svg" alt="" class="size-[12px]" />
        </button>
      </div>
      <div class="flex flex-1 flex-col gap-[4px] p-[8px] min-w-0 justify-center">
        <div class="flex items-center justify-between gap-[6px]">
          <p class="font-medium text-[13px] truncate" style="color:var(--color-card-title)">${p.name}</p>
          <p class="font-semibold text-[11px] shrink-0" style="color:var(--color-primary)">${p.weight}</p>
        </div>
        <div class="flex items-center gap-[6px]">
          <img src="assets/icons/icon-star-rating.svg" alt="" class="h-[8px] w-[56px]" />
          <span class="text-[11px] tracking-[0.3px]" style="color:var(--color-text-subtitle)">${p.rating}</span>
        </div>
        <div class="flex items-center gap-[6px]">
          <p class="font-bold text-[14px]" style="color:var(--color-souqify-teal)">${p.price}</p>
          ${p.oldPrice ? `<p class="text-[10px] line-through" style="color:var(--color-gray)">${p.oldPrice}</p>` : ""}
        </div>
      </div>
    </div>`;
}

function renderFlashComboSlide(products) {
  return `
    <div class="swiper-slide h-auto !w-[260px]">
      <div class="flex flex-col gap-[10px] h-full">
        ${products.map(renderFlashMiniCard).join("")}
      </div>
    </div>`;
}

function mountFlashSaleDesktop() {
  const wrapper = document.getElementById("flashSaleWrapperDesktop");
  if (!wrapper) return;
  const p = PRODUCTS.flashSale;
  wrapper.innerHTML = [
    renderProductCard(p[0]),
    renderFlashComboSlide([p[1], p[2], p[3]]),
    renderProductCard(p[2]),
    renderProductCard(p[1]),
  ].join("");

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    navigation: { prevEl: "#flashSalePrevDesktop", nextEl: "#flashSaleNextDesktop" },
  });
}

function mountCarousel({ wrapperId, products, prevId, nextId }) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;
  wrapper.innerHTML = products.map(renderProductCard).join("");

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    navigation: {
      prevEl: `#${prevId}`,
      nextEl: `#${nextId}`,
    },
  });
}

function renderGrid(containerId, products) {
  const el = document.getElementById(containerId);
  if (!el) return;
  el.innerHTML = products
    .map(
      (p) => `<div class="!w-full">${renderProductCard(p).replace('class="swiper-slide h-auto !w-[170px] lg:!w-[250px]"', 'class="h-full"')}</div>`
    )
    .join("");
}

function initCarousels() {
  mountCarousel({ wrapperId: "newInWrapper", products: PRODUCTS.newIn, prevId: "newInPrev", nextId: "newInNext" });
  mountCarousel({ wrapperId: "trendingWrapper", products: PRODUCTS.trendingNow, prevId: "trendingPrev", nextId: "trendingNext" });
  mountCarousel({ wrapperId: "flashSaleWrapper", products: PRODUCTS.flashSale, prevId: "flashSalePrev", nextId: "flashSaleNext" });
  mountFlashSaleDesktop();
  mountCarousel({ wrapperId: "bestSellerWrapper", products: PRODUCTS.bestSeller, prevId: "bestSellerPrev", nextId: "bestSellerNext" });

  // Shop by Category: static markup (not JS-rendered), just needs the Swiper instance.
  const shopByCategoryWrapper = document.getElementById("shopByCategoryWrapper");
  if (shopByCategoryWrapper) {
    new Swiper(shopByCategoryWrapper.closest(".swiper"), {
      slidesPerView: "auto",
      spaceBetween: 16,
      navigation: { prevEl: "#shopByCategoryPrev", nextEl: "#shopByCategoryNext" },
    });
  }
  renderGrid("recommendedGrid", PRODUCTS.recommended);

  // Flash-sale countdown (starts from the design's captured 03:06:25 and ticks down).
  // Mobile and desktop each render their own timer elements (data-attribute, not id,
  // since the desktop layout duplicates them in a rotated vertical stack), so every
  // matching element is updated together.
  let remaining = 3 * 3600 + 6 * 60 + 25;
  const hEls = document.querySelectorAll("[data-flash-hours]");
  const mEls = document.querySelectorAll("[data-flash-minutes]");
  const sEls = document.querySelectorAll("[data-flash-seconds]");
  if (hEls.length || mEls.length || sEls.length) {
    setInterval(() => {
      remaining = Math.max(0, remaining - 1);
      const h = String(Math.floor(remaining / 3600)).padStart(2, "0");
      const m = String(Math.floor((remaining % 3600) / 60)).padStart(2, "0");
      const s = String(remaining % 60).padStart(2, "0");
      hEls.forEach((el) => (el.textContent = h));
      mEls.forEach((el) => (el.textContent = m));
      sEls.forEach((el) => (el.textContent = s));
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
