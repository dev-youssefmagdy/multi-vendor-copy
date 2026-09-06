// Product card rendering + Swiper carousel wiring for the Souqify home screen.
// Card markup mirrors the Figma "Mobile Card" (New In, node 2019:24493) and
// "Deal Card" (Trending / Best Seller / Recommended, node 2019:24717) components.
// Product photography is intentionally replaced with an inline placeholder icon
// per the client's product-image-placeholder rule.

const PRODUCT_IMG_PLACEHOLDER = `
  <div class="product-placeholder absolute inset-0">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
      <rect x="3" y="3" width="18" height="18" rx="2"></rect>
      <circle cx="8.5" cy="8.5" r="1.5"></circle>
      <path d="M21 15l-5-5L5 21"></path>
    </svg>
  </div>`;

const NEW_IN_PRODUCTS = [
  { name: "Essential Shoes", weight: "250g", desc: "Premium cotton blend", rating: "4.2 (+850)", price: "$89.00", oldPrice: "$89.00", discount: "20% Off", left: "Only 5 left" },
  { name: "Studio Headphones", weight: "200g", desc: "Premium cotton blend", rating: "4.4 (+610)", price: "$129.00", oldPrice: "$159.00", discount: "18% Off", left: "Only 3 left" },
  { name: "Wireless Earbuds", weight: "60g", desc: "Premium cotton blend", rating: "4.6 (+1.1k)", price: "$59.00", oldPrice: null, discount: null, left: "Only 8 left" },
  { name: "Everyday Sneakers", weight: "270g", desc: "Premium cotton blend", rating: "4.3 (+430)", price: "$95.00", oldPrice: "$120.00", discount: "15% Off", left: "Only 4 left" },
  { name: "Studio Headphones Pro", weight: "215g", desc: "Premium cotton blend", rating: "4.1 (+320)", price: "$139.00", oldPrice: null, discount: null, left: "Only 6 left" },
  { name: "Sport Earbuds", weight: "55g", desc: "Premium cotton blend", rating: "4.5 (+900)", price: "$65.00", oldPrice: "$79.00", discount: "17% Off", left: "Only 2 left" },
];

const DEAL_PRODUCTS = {
  trending: [
    { name: "SonicFlow Wireless Studio Headphones Gen 2", weight: "200g", rating: "4.2 (+850)", price: "$149.00", oldPrice: "$199.00", discount: "50% off", badgeBg: "var(--color-error)", badgeText: "var(--color-white)" },
    { name: "NovaTab Pro 11 Tablet", weight: "460g", rating: "4.5 (+610)", price: "$389.00", oldPrice: "$519.00", discount: "25% off", badgeBg: "var(--color-discount-orange)", badgeText: "var(--color-white)" },
    { name: "PulseBuds True Wireless Earbuds", weight: "50g", rating: "4.4 (+1.2k)", price: "$79.00", oldPrice: "$109.00", discount: "35% off", badgeBg: "var(--color-discount-yellow)", badgeText: "var(--color-black)" },
    { name: "AeroPhone X12 Smartphone", weight: "185g", rating: "4.3 (+980)", price: "$699.00", oldPrice: "$899.00", discount: "22% off", badgeBg: "var(--color-error)", badgeText: "var(--color-white)" },
    { name: "SonicFlow Wireless Studio Headphones Gen 2", weight: "200g", rating: "4.2 (+850)", price: "$149.00", oldPrice: "$199.00", discount: "50% off", badgeBg: "var(--color-error)", badgeText: "var(--color-white)" },
    { name: "PulseBuds True Wireless Earbuds", weight: "50g", rating: "4.4 (+1.2k)", price: "$79.00", oldPrice: "$109.00", discount: "35% off", badgeBg: "var(--color-discount-yellow)", badgeText: "var(--color-black)" },
  ],
  bestSeller: [
    { name: "AeroPhone X12 Smartphone", weight: "185g", rating: "4.6 (+2.1k)", price: "$699.00", oldPrice: "$899.00", discount: "22% off", badgeBg: "var(--color-error)", badgeText: "var(--color-white)" },
    { name: "SonicFlow Wireless Studio Headphones Gen 2", weight: "200g", rating: "4.7 (+1.8k)", price: "$149.00", oldPrice: "$199.00", discount: "50% off", badgeBg: "var(--color-discount-orange)", badgeText: "var(--color-white)" },
    { name: "PulseBuds True Wireless Earbuds", weight: "50g", rating: "4.6 (+1.6k)", price: "$79.00", oldPrice: "$109.00", discount: "35% off", badgeBg: "var(--color-discount-yellow)", badgeText: "var(--color-black)" },
    { name: "NovaTab Pro 11 Tablet", weight: "460g", rating: "4.5 (+920)", price: "$389.00", oldPrice: "$519.00", discount: "25% off", badgeBg: "var(--color-error)", badgeText: "var(--color-white)" },
  ],
  recommended: [
    { name: "NovaTab Pro 11 Tablet", weight: "460g", rating: "4.5 (+610)", price: "$389.00", oldPrice: "$519.00", discount: "25% off", badgeBg: "var(--color-error)", badgeText: "var(--color-white)" },
    { name: "PulseBuds True Wireless Earbuds", weight: "50g", rating: "4.4 (+1.2k)", price: "$79.00", oldPrice: "$109.00", discount: "35% off", badgeBg: "var(--color-discount-yellow)", badgeText: "var(--color-black)" },
    { name: "SonicFlow Wireless Studio Headphones Gen 2", weight: "200g", rating: "4.2 (+850)", price: "$149.00", oldPrice: "$199.00", discount: "50% off", badgeBg: "var(--color-discount-orange)", badgeText: "var(--color-white)" },
    { name: "AeroPhone X12 Smartphone", weight: "185g", rating: "4.3 (+980)", price: "$699.00", oldPrice: "$899.00", discount: "22% off", badgeBg: "var(--color-error)", badgeText: "var(--color-white)" },
    { name: "Everyday Sneakers", weight: "270g", rating: "4.3 (+430)", price: "$95.00", oldPrice: "$120.00", discount: "15% off", badgeBg: "var(--color-discount-yellow)", badgeText: "var(--color-black)" },
    { name: "Studio Headphones Pro", weight: "215g", rating: "4.1 (+320)", price: "$139.00", oldPrice: null, discount: null, badgeBg: "var(--color-discount-orange)", badgeText: "var(--color-white)" },
  ],
};

const FLASH_PRODUCTS = [
  { eyebrow: "Nike shoes", name: "Veloce Speed-X Trainers", price: "$789.99", oldPrice: "$999", sold: "125 couple", left: "5 left", pct: 82, h: "04", m: "22", s: "57" },
  { eyebrow: "Nike shoes", name: "Veloce Speed-X Trainers", price: "$789.99", oldPrice: "$999", sold: "125 couple", left: "5 left", pct: 82, h: "04", m: "22", s: "57" },
  { eyebrow: "Sonic Audio", name: "Aria Bass Headphones", price: "$249.99", oldPrice: "$349", sold: "88 couple", left: "3 left", pct: 65, h: "02", m: "10", s: "34" },
  { eyebrow: "Sonic Audio", name: "Aria Bass Headphones", price: "$249.99", oldPrice: "$349", sold: "88 couple", left: "3 left", pct: 65, h: "02", m: "10", s: "34" },
  { eyebrow: "Nike shoes", name: "Veloce Speed-X Trainers", price: "$789.99", oldPrice: "$999", sold: "125 couple", left: "5 left", pct: 82, h: "04", m: "22", s: "57" },
  { eyebrow: "Sonic Audio", name: "Aria Bass Headphones", price: "$249.99", oldPrice: "$349", sold: "88 couple", left: "3 left", pct: 65, h: "02", m: "10", s: "34" },
  { eyebrow: "Nike shoes", name: "Veloce Speed-X Trainers", price: "$789.99", oldPrice: "$999", sold: "125 couple", left: "5 left", pct: 82, h: "04", m: "22", s: "57" },
  { eyebrow: "Sonic Audio", name: "Aria Bass Headphones", price: "$249.99", oldPrice: "$349", sold: "88 couple", left: "3 left", pct: 65, h: "02", m: "10", s: "34" },
];

function renderNewInCard(p) {
  return `
    <div class="swiper-slide h-auto !w-[200px] lg:!w-[227px]">
      <div class="bg-[var(--color-bg-main)] flex flex-col items-start rounded-[10px] h-full shadow-[var(--shadow-card)]">
        <div class="relative w-full h-[145px] lg:h-[165px] rounded-t-[10px] overflow-hidden shrink-0">
          ${PRODUCT_IMG_PLACEHOLDER}
          <span class="absolute top-0 left-0 text-[12px] font-normal px-[8px] py-[4px] rounded-br-[10px]" style="background:var(--color-accent-yellow); color:var(--color-black)">70% Sold</span>
          <button type="button" aria-label="Add to favorites" class="absolute top-[6px] right-[6px] bg-white rounded-full p-[6px] shadow"><img src="assets/icons/newin-heart.svg" class="size-[16px]" alt="" /></button>
          <div class="absolute bottom-[6px] right-[6px] bg-[var(--color-bg-main)] rounded-full p-[6px] shadow"><img src="assets/icons/newin-cart.svg" class="size-[20px]" alt="" /></div>
        </div>
        <div class="flex-1 w-full p-[10px] flex flex-col gap-[6px]">
          <div class="flex items-center justify-between">
            <p class="font-medium text-[16px]" style="color:var(--color-text-primary)">${p.name}</p>
            <p class="text-[14px]" style="color:var(--color-primary)">${p.weight}</p>
          </div>
          <p class="text-[13px]" style="color:var(--color-text-subtitle)">${p.desc}</p>
          <div class="flex items-center gap-[6px]">
            <img src="assets/icons/newin-star-rating.svg" alt="" class="h-[10px] w-[69px]" />
            <span class="text-[12px]" style="color:var(--color-text-subtitle)">${p.rating}</span>
          </div>
          <div class="flex items-end gap-[6px] flex-wrap">
            <p class="font-medium text-[16px]" style="color:var(--color-primary)">${p.price}</p>
            ${p.oldPrice ? `<p class="text-[12px] line-through font-light" style="color:var(--color-text-subtitle)">${p.oldPrice}</p>` : ""}
            ${p.discount ? `<p class="text-[12px]" style="color:var(--color-secondary)">${p.discount}</p>` : ""}
          </div>
          <div class="flex items-center gap-[6px]">
            <img src="assets/icons/newin-truck.svg" alt="" class="size-[16px]" />
            <p class="text-[11px] font-medium" style="color:var(--color-success)">Delivered by 24 March</p>
          </div>
          <div class="flex items-center gap-[6px]">
            <img src="assets/icons/newin-cartx.svg" alt="" class="size-[13px]" />
            <p class="text-[11px] font-medium" style="color:var(--color-success)">${p.left}</p>
          </div>
        </div>
      </div>
    </div>`;
}

function renderDealCard(p) {
  return `
    <div class="swiper-slide h-auto !w-[190px] lg:!w-[226px]">
      <div class="flex flex-col items-start rounded-[6px] h-full overflow-hidden shadow-[var(--shadow-card)]" style="background:var(--color-card-bg-lavender)">
        <div class="relative w-full h-[210px] lg:h-[236px] shrink-0">
          ${PRODUCT_IMG_PLACEHOLDER}
          <div class="absolute bottom-0 left-1/2 -translate-x-1/2 bg-white flex items-center justify-center rounded-[6px] shadow size-[42px]">
            <img src="assets/icons/trending-cart.svg" class="size-[26px]" alt="Add to cart" />
          </div>
          <button type="button" aria-label="Add to favorites" class="absolute top-[6px] right-[6px] bg-white rounded-full p-[7px] shadow"><img src="assets/icons/trending-heart.svg" class="size-[18px]" alt="" /></button>
          <div class="ribbon-red-gradient absolute bottom-0 left-0 flex items-center gap-[8px] px-[6px] py-[3px] rounded-tr-[18px]">
            <img src="assets/icons/trending-truck.svg" class="size-[16px]" alt="" />
            <span class="text-[10px] font-medium whitespace-nowrap" style="color:var(--color-black)">Only 5 left</span>
          </div>
        </div>
        <div class="bg-white w-full flex flex-col gap-[4px] px-[8px] py-[9px]">
          <div class="flex items-start justify-between gap-[4px]">
            <p class="font-medium text-[15px] truncate" style="color:var(--color-charcoal-text)">${p.name}</p>
            <p class="font-semibold text-[13px] whitespace-nowrap" style="color:var(--color-weight-tag)">${p.weight}</p>
          </div>
          <div class="flex items-center gap-[6px]">
            <img src="assets/icons/trending-star-rating.svg" alt="" class="h-[9px] w-[62px]" />
            <span class="text-[12px]" style="color:var(--color-text-subtitle)">${p.rating}</span>
          </div>
          <div class="flex items-center justify-between gap-[6px]">
            <div class="flex items-center gap-[5px]">
              <span class="font-bold text-[17px]" style="color:var(--color-primary)">${p.price}</span>
              ${p.oldPrice ? `<span class="text-[11px] line-through" style="color:var(--color-gray)">${p.oldPrice}</span>` : ""}
            </div>
            ${p.discount ? `<span class="text-[10px] tracking-[0.5px] px-[6px] py-[4px] whitespace-nowrap" style="background:${p.badgeBg}; color:${p.badgeText}">${p.discount}</span>` : ""}
          </div>
        </div>
      </div>
    </div>`;
}

function renderFlashCard(p) {
  return `
    <div class="bg-white flex gap-[10px] items-center p-[10px] rounded-[10px] w-full lg:w-[340px]">
      <div class="relative rounded-[7px] shrink-0 size-[100px] lg:size-[118px] overflow-hidden">
        ${PRODUCT_IMG_PLACEHOLDER}
        <span class="ribbon-red-gradient absolute top-[6px] left-0 text-white text-[10px] font-semibold px-[6px] py-[2px] rounded-r-[4px]">-19%</span>
        <div class="absolute bg-white flex items-center justify-center rounded-[8px] shadow size-[36px]" style="left:68px; top:70px">
          <img src="assets/icons/flash-cart.svg" class="size-[22px]" alt="" />
        </div>
      </div>
      <div class="flex flex-col gap-[6px] flex-1 min-w-0">
        <p class="text-[9px] tracking-[0.5px]" style="color:var(--color-desc-gray)">${p.eyebrow}</p>
        <p class="font-medium text-[15px] truncate" style="color:var(--color-navy-text)">${p.name}</p>
        <div class="flex items-center gap-[4px]">
          <span class="font-semibold text-[13px]" style="color:var(--color-price-alt-blue)">${p.price}</span>
          <span class="text-[10px] line-through" style="color:var(--color-strike-gray)">${p.oldPrice}</span>
        </div>
        <div class="flex flex-col gap-[2px] w-full">
          <div class="h-[4px] w-full rounded-full" style="background:var(--color-progress-track)">
            <div class="h-full rounded-full" style="background:var(--color-error); width:${p.pct}%"></div>
          </div>
          <div class="flex items-center justify-between text-[10px]">
            <span style="color:var(--color-desc-gray)">Sold: <span style="color:var(--color-flash-timer-blue)">${p.sold}</span></span>
            <span style="color:var(--color-error)">${p.left}</span>
          </div>
        </div>
        <div class="flex items-center gap-[4px]">
          <div class="flex-1 flex items-center justify-center rounded-[8px] py-[6px]" style="background:var(--color-page-bg)"><span class="font-bold text-[13px]" style="color:var(--color-flash-timer-blue)">${p.h} :</span></div>
          <div class="flex-1 flex items-center justify-center rounded-[8px] py-[6px]" style="background:var(--color-page-bg)"><span class="font-bold text-[13px]" style="color:var(--color-flash-timer-blue)">${p.m} :</span></div>
          <div class="flex-1 flex items-center justify-center rounded-[8px] py-[6px]" style="background:var(--color-page-bg)"><span class="font-bold text-[13px]" style="color:var(--color-flash-timer-blue)">${p.s}</span></div>
        </div>
      </div>
    </div>`;
}

function mountCarousel({ wrapperId, products, render, prevId, nextId }) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;
  wrapper.innerHTML = products.map(render).join("");

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    navigation: prevId && nextId ? { prevEl: `#${prevId}`, nextEl: `#${nextId}` } : undefined,
  });
}

function mountFlashCards() {
  const wrapper = document.getElementById("flashWrapper");
  if (!wrapper) return;
  wrapper.innerHTML = FLASH_PRODUCTS.map(renderFlashCard).join("");
}

function startCountdown() {
  let remaining = 3 * 3600 + 6 * 60 + 25;
  const hEl = document.getElementById("flashH");
  const mEl = document.getElementById("flashM");
  const sEl = document.getElementById("flashS");
  if (!hEl || !mEl || !sEl) return;
  setInterval(() => {
    remaining = Math.max(0, remaining - 1);
    hEl.textContent = String(Math.floor(remaining / 3600)).padStart(2, "0");
    mEl.textContent = String(Math.floor((remaining % 3600) / 60)).padStart(2, "0");
    sEl.textContent = String(remaining % 60).padStart(2, "0");
  }, 1000);
}

function initCarousels() {
  mountCarousel({ wrapperId: "newInWrapper", products: NEW_IN_PRODUCTS, render: renderNewInCard, prevId: "newInPrev", nextId: "newInNext" });
  mountCarousel({ wrapperId: "trendingWrapper", products: DEAL_PRODUCTS.trending, render: renderDealCard, prevId: "trendingPrev", nextId: "trendingNext" });
  mountCarousel({ wrapperId: "bestSellerWrapper", products: DEAL_PRODUCTS.bestSeller, render: renderDealCard, prevId: "bestSellerPrev", nextId: "bestSellerNext" });
  mountCarousel({ wrapperId: "recommendedWrapper", products: DEAL_PRODUCTS.recommended, render: renderDealCard, prevId: "recommendedPrev", nextId: "recommendedNext" });
  mountFlashCards();
  startCountdown();
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
