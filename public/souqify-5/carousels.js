// Product card rendering + Swiper carousel wiring for the Souqify home screen.
// Card markup mirrors the Figma "Deal Card" component (node 2019:25266) and the
// "Mobile Card" mini list item used in Trending Now (node 2019:25012).
//
// PRODUCT IMAGE PLACEHOLDER RULE: per client instruction, product-card thumbnails
// (New In / Trending Now / Flash Sale / Best Seller / Recommended For You) render
// an inline placeholder icon instead of downloaded product photography.

const BADGE_CYCLE = ["Trending Now", "Verified Quality", "Free Shipping", "Free Gift", "Limited Time"];

function placeholderIcon(sizeClass) {
  return `
    <div class="product-img-placeholder absolute inset-0 rounded-t-[8px]">
      <svg viewBox="0 0 24 24" class="${sizeClass}" fill="none" stroke="var(--color-gray)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="18" height="18" rx="2"/>
        <circle cx="8.5" cy="8.5" r="1.5"/>
        <path d="M21 15l-5-5L5 21"/>
      </svg>
    </div>`;
}

const DEAL_PRODUCTS = Array.from({ length: 8 }, (_, i) => ({
  badge: BADGE_CYCLE[i % BADGE_CYCLE.length],
  name: "SonicFlow Wireless Studio Headphones Gen 2",
  weight: "200g",
  rating: "4.2 (+850)",
  price: "$149.00",
  oldPrice: "$199.00",
  discount: "50% off",
  delivery: "Delivered by 24 March",
  stock: "Only 5 left - Hurry up",
}));

const TRENDING_PRODUCTS = [
  { name: "Essential Shoes", desc: "Premium cotton blend", weight: "250g", rating: "4.2 (+850)", price: "$89.00", oldPrice: "$89.00", discount: "20% Off" },
  { name: "Smart Watch Pro", desc: "Premium cotton blend", weight: "180g", rating: "4.5 (+620)", price: "$129.00", oldPrice: null, discount: null },
  { name: "Classic Sneakers", desc: "Premium cotton blend", weight: "260g", rating: "4.1 (+430)", price: "$79.00", oldPrice: "$99.00", discount: "20% Off" },
  { name: "Running Shoes", desc: "Premium cotton blend", weight: "240g", rating: "4.6 (+1.2k)", price: "$110.00", oldPrice: "$130.00", discount: "15% Off" },
  { name: "Trail Sneakers", desc: "Premium cotton blend", weight: "270g", rating: "4.4 (+560)", price: "$115.00", oldPrice: null, discount: null },
  { name: "Court Sneakers", desc: "Premium cotton blend", weight: "255g", rating: "4.3 (+390)", price: "$92.00", oldPrice: null, discount: null },
];

function renderDealCard(p) {
  return `
    <div class="swiper-slide h-auto !w-[160px] lg:!w-[266px]">
      <div class="flex flex-col items-start rounded-[8px] h-full overflow-hidden" style="background:var(--color-bg-main)">
        <div class="relative w-full h-[183px] lg:h-[227px] shrink-0">
          ${placeholderIcon("size-[32%]")}
          <span class="delivery-ribbon absolute left-0 bottom-0 flex items-center gap-[6px] px-[6px] py-[3px] rounded-tr-[10px]">
            <img src="assets/icons/truck-delivery.svg" alt="" class="size-[13px]" />
            <span class="text-[9px] font-medium whitespace-nowrap" style="color:var(--color-black)">${p.delivery}</span>
          </span>
          <span class="absolute left-0 top-0 flex items-center gap-[4px] px-[8px] py-[4px] rounded-br-[12px]" style="background:var(--color-brand-pink)">
            <span class="text-[11px] font-medium text-white tracking-[0.4px] whitespace-nowrap">${p.badge}</span>
          </span>
          <button type="button" aria-label="Add to favorites" class="absolute top-[8px] right-[8px] bg-white rounded-full p-[7px] shadow-md">
            <img src="assets/icons/heart.svg" class="size-[16px]" alt="" />
          </button>
        </div>
        <div class="flex flex-col gap-[6px] p-[8px] w-full">
          <div class="flex items-start justify-between gap-[4px]">
            <p class="font-medium text-[14px] truncate" style="color:var(--color-text-heading)">${p.name}</p>
            <p class="font-semibold text-[12px] shrink-0" style="color:var(--color-primary)">${p.weight}</p>
          </div>
          <div class="flex items-center gap-[5px]">
            <img src="assets/icons/star-rating.svg" alt="" class="h-[8px] w-[58px]" />
            <span class="text-[12px] tracking-[0.3px]" style="color:var(--color-text-subtitle)">${p.rating}</span>
          </div>
          <div class="flex items-center justify-between w-full">
            <span class="flex items-end gap-[4px]">
              <span class="font-bold text-[16px]" style="color:var(--color-brand-pink)">${p.price}</span>
              <span class="text-[10px] line-through" style="color:var(--color-gray)">${p.oldPrice}</span>
            </span>
            <span class="text-white text-[9px] tracking-[0.4px] px-[6px] py-[3px]" style="background:var(--color-error)">${p.discount}</span>
          </div>
        </div>
      </div>
    </div>`;
}

function renderTrendingCard(p) {
  return `
    <div class="swiper-slide h-auto !w-[260px] lg:!w-[380px]">
      <div class="flex h-full rounded-[10px] overflow-hidden" style="background:var(--color-bg-main); box-shadow:var(--shadow-card)">
        <div class="relative w-[124px] shrink-0">
          ${placeholderIcon("size-[34%]")}
          <button type="button" aria-label="Add to favorites" class="absolute top-[6px] left-[6px] bg-white rounded-full p-[5px] shadow"><img src="assets/icons/heart.svg" class="size-[14px]" alt="" /></button>
          <div class="absolute bottom-[6px] right-[6px] rounded-[8px] p-[5px] shadow" style="background:var(--color-black-alt)"><img src="assets/icons/cart-add.svg" class="size-[16px] invert" alt="" /></div>
        </div>
        <div class="flex-1 p-[10px] flex flex-col gap-[6px] min-w-0">
          <div class="flex items-center justify-between gap-[4px]">
            <p class="font-medium text-[14px] truncate" style="color:var(--color-text-heading)">${p.name}</p>
            <span class="text-[12px] shrink-0" style="color:var(--color-brand-pink)">${p.weight}</span>
          </div>
          <p class="text-[12px] truncate" style="color:var(--color-text-subtitle)">${p.desc}</p>
          <div class="flex items-center gap-[5px]">
            <img src="assets/icons/star-rating.svg" alt="" class="h-[8px] w-[56px]" />
            <span class="text-[11px]" style="color:var(--color-text-subtitle)">${p.rating}</span>
          </div>
          <div class="flex items-end gap-[6px]">
            <span class="font-medium text-[15px]" style="color:var(--color-text-heading)">${p.price}</span>
            ${p.oldPrice ? `<span class="text-[10px] line-through" style="color:var(--color-gray)">${p.oldPrice}</span>` : ""}
            ${p.discount ? `<span class="text-[11px]" style="color:var(--color-brand-pink)">${p.discount}</span>` : ""}
          </div>
        </div>
      </div>
    </div>`;
}

function mountCarousel({ selector, wrapperId, products, render, prevId, nextId }) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;
  wrapper.innerHTML = products.map(render).join("");

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    navigation: prevId && nextId ? { prevEl: `#${prevId}`, nextEl: `#${nextId}` } : undefined,
  });
}

function renderRecommendedGrid() {
  const grid = document.getElementById("recommendedGrid");
  if (!grid) return;
  const items = Array.from({ length: 10 }, (_, i) => DEAL_PRODUCTS[i % DEAL_PRODUCTS.length]);
  grid.innerHTML = items
    .map(
      (p) => `
    <div class="flex flex-col items-start rounded-[8px] overflow-hidden" style="background:var(--color-bg-main); box-shadow:var(--shadow-card)">
      <div class="relative w-full aspect-[160/183]">
        ${placeholderIcon("size-[32%]")}
        <span class="delivery-ribbon absolute left-0 bottom-0 flex items-center gap-[4px] px-[5px] py-[2px] rounded-tr-[8px]">
          <img src="assets/icons/truck-delivery.svg" alt="" class="size-[11px]" />
          <span class="text-[7px] font-medium whitespace-nowrap" style="color:var(--color-black)">${p.delivery}</span>
        </span>
        <span class="absolute left-0 top-0 flex items-center px-[6px] py-[3px] rounded-br-[10px]" style="background:var(--color-brand-pink)">
          <span class="text-[9px] font-medium text-white tracking-[0.3px] whitespace-nowrap">${p.badge}</span>
        </span>
        <button type="button" aria-label="Add to favorites" class="absolute top-[6px] right-[6px] bg-white rounded-full p-[5px] shadow-md">
          <img src="assets/icons/heart.svg" class="size-[13px]" alt="" />
        </button>
      </div>
      <div class="flex flex-col gap-[4px] p-[8px] w-full">
        <div class="flex items-start justify-between gap-[4px]">
          <p class="font-medium text-[13px] truncate" style="color:var(--color-text-heading)">${p.name}</p>
          <p class="font-semibold text-[11px] shrink-0" style="color:var(--color-primary)">${p.weight}</p>
        </div>
        <div class="flex items-center gap-[4px]">
          <img src="assets/icons/star-rating.svg" alt="" class="h-[7px] w-[50px]" />
          <span class="text-[10px]" style="color:var(--color-text-subtitle)">${p.rating}</span>
        </div>
        <div class="flex items-center justify-between w-full">
          <span class="flex items-end gap-[4px]">
            <span class="font-bold text-[14px]" style="color:var(--color-brand-pink)">${p.price}</span>
            <span class="text-[9px] line-through" style="color:var(--color-gray)">${p.oldPrice}</span>
          </span>
          <span class="text-white text-[8px] tracking-[0.3px] px-[5px] py-[2px]" style="background:var(--color-error)">${p.discount}</span>
        </div>
      </div>
    </div>`
    )
    .join("");
}

function initCarousels() {
  mountCarousel({ wrapperId: "newInWrapper", products: DEAL_PRODUCTS, render: renderDealCard, prevId: "newInPrev", nextId: "newInNext" });
  mountCarousel({ wrapperId: "bestSellerWrapper", products: DEAL_PRODUCTS, render: renderDealCard, prevId: "bestSellerPrev", nextId: "bestSellerNext" });
  mountCarousel({ wrapperId: "trendingWrapper", products: TRENDING_PRODUCTS, render: renderTrendingCard, prevId: "trendingPrev", nextId: "trendingNext" });
  renderRecommendedGrid();

  // Flash-sale countdown (starts from the design's captured 03:06:25 and ticks down)
  let remaining = 3 * 3600 + 6 * 60 + 25;
  const hoursEl = document.querySelector('[data-countdown="hours"]');
  const minutesEl = document.querySelector('[data-countdown="minutes"]');
  const secondsEl = document.querySelector('[data-countdown="seconds"]');
  if (hoursEl && minutesEl && secondsEl) {
    setInterval(() => {
      remaining = Math.max(0, remaining - 1);
      hoursEl.textContent = String(Math.floor(remaining / 3600)).padStart(2, "0");
      minutesEl.textContent = String(Math.floor((remaining % 3600) / 60)).padStart(2, "0");
      secondsEl.textContent = String(remaining % 60).padStart(2, "0");
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
