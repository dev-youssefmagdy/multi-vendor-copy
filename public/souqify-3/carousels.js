// Product card rendering + Swiper carousel wiring for the Souqify home screen.
// Card markup mirrors the Figma "Deal Card" component (node 2019:23897 family).
// Per client instruction, product thumbnail photography is NOT downloaded/fetched for
// New In / Trending Now / Recommended For You / Best Seller / Flash Sale cards — each
// image slot instead renders a placeholder box sized to the Figma slot, using the
// project's neutral fill tokens plus an inline outlined "photo" icon.

const PLACEHOLDER_ICON = `
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <rect x="3" y="3" width="18" height="18" rx="2"></rect>
    <circle cx="8.5" cy="8.5" r="1.5"></circle>
    <path d="M21 15l-5-5L5 21"></path>
  </svg>`;

function placeholderBox(extraClasses) {
  return `<div class="product-placeholder absolute inset-0 ${extraClasses || ""}">${PLACEHOLDER_ICON}</div>`;
}

const DISCOUNT_STYLES = [
  { label: "35% off", bg: "var(--color-badge-yellow)", text: "var(--color-text-primary)" },
  { label: "25% off", bg: "var(--color-badge-orange)", text: "var(--color-white)" },
  { label: "50% off", bg: "var(--color-error)", text: "var(--color-white)" },
];

const DEAL_PRODUCTS = [
  { name: "SonicFlow Wireless Studio Headphones Gen 2", weight: "200g", rating: "4.2 (+850)", price: "$149.00", oldPrice: "$199.00" },
  { name: "SonicFlow Wireless Studio Headphones Gen 2", weight: "200g", rating: "4.2 (+850)", price: "$149.00", oldPrice: "$199.00" },
  { name: "SonicFlow Wireless Studio Headphones Gen 2", weight: "200g", rating: "4.2 (+850)", price: "$149.00", oldPrice: "$199.00" },
  { name: "SonicFlow Wireless Studio Headphones Gen 2", weight: "200g", rating: "4.2 (+850)", price: "$149.00", oldPrice: "$199.00" },
  { name: "SonicFlow Wireless Studio Headphones Gen 2", weight: "200g", rating: "4.2 (+850)", price: "$149.00", oldPrice: "$199.00" },
  { name: "SonicFlow Wireless Studio Headphones Gen 2", weight: "200g", rating: "4.2 (+850)", price: "$149.00", oldPrice: "$199.00" },
];

// Renders the inner "Deal Card" markup — used by New In, Best Seller, Recommended.
function dealCardInner(product, index) {
  const discount = DISCOUNT_STYLES[index % DISCOUNT_STYLES.length];
  return `
      <div class="flex flex-col items-start rounded-[5px] h-full overflow-hidden" style="background:var(--color-white)">
        <div class="relative w-full h-[183px] lg:h-[227px] shrink-0" style="background:var(--color-card-image-bg)">
          ${placeholderBox()}
          <span class="absolute top-0 left-0 flex items-center gap-[4px] rounded-tl-[5px] rounded-br-[10px] px-[8px] py-[4px] text-white text-[10px] lg:text-[12px] font-medium tracking-[0.5px] whitespace-nowrap" style="background:var(--color-brand-green)">🔥 Trending Now</span>
          <button type="button" aria-label="Add to favorites" class="absolute top-[5px] right-[5px] bg-white rounded-full p-[7px] shadow cursor-pointer">
            <img src="assets/icons/icon-heart.svg" alt="" class="size-[16px]" />
          </button>
          <div class="absolute left-0 bottom-0 flex flex-col gap-[2px] justify-end px-[8px] py-[6px] w-[70%] rounded-tr-[16px] text-white" style="background:var(--color-error)">
            <div class="flex items-center gap-[6px]">
              <img src="assets/icons/icon-truck-delivery.svg" alt="" class="size-[12px]" />
              <span class="text-[8px] lg:text-[10px] font-medium" style="color:var(--color-text-primary)">Delivered by 24 March</span>
            </div>
            <div class="flex items-center gap-[4px]">
              <img src="assets/icons/icon-cart-x.svg" alt="" class="size-[10px]" />
              <span class="text-[8px] font-medium whitespace-nowrap">Only 5 left - Hurry up</span>
            </div>
          </div>
          <div class="absolute bottom-[6px] right-[6px] bg-white rounded-[5px] p-[6px] shadow flex items-center justify-center">
            <img src="assets/icons/icon-cart-badge.svg" alt="Add to cart" class="size-[20px]" />
          </div>
        </div>
        <div class="flex flex-col gap-[6px] items-start p-[6px] w-full">
          <div class="flex items-start justify-between w-full gap-[4px]">
            <p class="font-medium text-[14px] overflow-hidden text-ellipsis whitespace-nowrap" style="color:var(--color-text-primary)">${product.name}</p>
            <p class="font-semibold text-[12px] text-center whitespace-nowrap shrink-0" style="color:var(--color-primary)">${product.weight}</p>
          </div>
          <div class="flex gap-[5px] items-center justify-center">
            <img src="assets/icons/star-rating.svg" alt="" class="h-[8px] w-[57px]" />
            <span class="text-[12px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-text-subtitle)">${product.rating}</span>
          </div>
          <div class="flex items-center justify-between w-full">
            <div class="flex gap-[4px] items-center whitespace-nowrap">
              <span class="font-bold text-[16px]" style="color:var(--color-brand-green)">${product.price}</span>
              <span class="text-[10px] line-through" style="color:var(--color-gray)">${product.oldPrice}</span>
            </div>
            <div class="flex items-center justify-center px-[6px] py-[3px]" style="background:${discount.bg}">
              <span class="text-[9px] tracking-[0.5px] whitespace-nowrap" style="color:${discount.text}">${discount.label}</span>
            </div>
          </div>
        </div>
      </div>`;
}

function renderDealCard(product, index) {
  return `<div class="swiper-slide h-auto !w-[160px] lg:!w-[210px]">${dealCardInner(product, index)}</div>`;
}

// Renders one large horizontal "Mobile Card" — used by Trending Now.
const TRENDING_PRODUCTS = [
  { name: "Essential Shoes", weight: "250g", ordered: "5 ordered last 30 min", progress: 83, rating: "4.2 (+850)", price: "$89.00", oldPrice: "$89.00", discount: "20% Off" },
  { name: "Essential Shoes", weight: "250g", ordered: "3 ordered last 30 min", progress: 65, rating: "4.2 (+850)", price: "$89.00", oldPrice: "$89.00", discount: "20% Off" },
  { name: "Essential Shoes", weight: "250g", ordered: "2 ordered last 30 min", progress: 45, rating: "4.2 (+850)", price: "$89.00", oldPrice: "$89.00", discount: "20% Off" },
];

function renderTrendingCard(product) {
  return `
    <div class="flex rounded-[10px] shadow-sm shrink-0 lg:w-[463px] overflow-hidden" style="background:var(--color-bg-main)">
      <div class="relative w-[38%] lg:w-[42%] shrink-0" style="background:var(--color-card-image-bg)">
        ${placeholderBox()}
        <span class="absolute top-0 left-0 text-white text-[11px] lg:text-[12px] font-normal px-[8px] py-[4px] rounded-br-[8px]" style="background:var(--color-brand-green)">70% Sold</span>
        <button type="button" aria-label="Add to favorites" class="absolute top-[6px] right-[6px] bg-white rounded-full p-[5px] shadow cursor-pointer">
          <img src="assets/icons/icon-heart.svg" alt="" class="size-[16px]" />
        </button>
        <div class="absolute bottom-[8px] right-[8px] rounded-[8px] p-[6px] shadow" style="background:var(--color-bg-main)">
          <img src="assets/icons/icon-cart-badge.svg" alt="" class="size-[20px]" />
        </div>
      </div>
      <div class="flex-1 p-[12px] flex flex-col gap-[8px]">
        <div class="flex items-center justify-between">
          <p class="font-medium text-[16px]" style="color:var(--color-text-primary)">${product.name}</p>
          <p class="text-[14px]" style="color:var(--color-brand-green)">${product.weight}</p>
        </div>
        <p class="text-[13px]" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
        <div class="h-[6px] w-full rounded-full" style="background:var(--color-stroke)">
          <div class="h-full rounded-full" style="background:var(--color-brand-green); width:${product.progress}%"></div>
        </div>
        <p class="text-[11px]" style="color:var(--color-brand-green)">${product.ordered}</p>
        <div class="flex items-center gap-[6px]">
          <img src="assets/icons/star-rating.svg" alt="" class="h-[8px] w-[56px]" />
          <span class="text-[12px]" style="color:var(--color-text-subtitle)">${product.rating}</span>
        </div>
        <div class="flex items-center gap-[6px]">
          <img src="assets/icons/icon-truck-delivery.svg" alt="" class="size-[16px]" />
          <span class="text-[11px] font-medium" style="color:var(--color-success)">Delivered by 24 March</span>
        </div>
        <div class="flex items-end gap-[6px]">
          <p class="font-medium text-[16px]" style="color:var(--color-text-primary)">${product.price}</p>
          <p class="text-[11px] line-through" style="color:var(--color-text-subtitle)">${product.oldPrice}</p>
          <p class="text-[12px]" style="color:var(--color-error)">${product.discount}</p>
        </div>
      </div>
    </div>`;
}

function mountCarousel({ wrapperId, products, prevId, nextId }) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;
  wrapper.innerHTML = products.map((p, i) => renderDealCard(p, i)).join("");

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    navigation: prevId && nextId ? { prevEl: `#${prevId}`, nextEl: `#${nextId}` } : undefined,
  });
}

function mountBestSeller() {
  const wrapper = document.getElementById("bestSellerWrapper");
  if (!wrapper) return;
  wrapper.innerHTML = DEAL_PRODUCTS.map((p, i) => renderDealCard(p, i)).join("");

  new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    loop: false,
    autoplay: { delay: 3500, disableOnInteraction: false },
    pagination: {
      el: ".best-seller-pagination",
      clickable: true,
      bulletClass: "best-seller-dot",
      bulletActiveClass: "is-active",
    },
  });
}

function mountTrending() {
  const wrapper = document.getElementById("trendingWrapper");
  if (!wrapper) return;
  wrapper.innerHTML = TRENDING_PRODUCTS.map(renderTrendingCard).join("");
}

function mountRecommendedGrid() {
  const grid = document.getElementById("recommendedGrid");
  if (!grid) return;
  const items = [];
  for (let i = 0; i < 10; i++) items.push(DEAL_PRODUCTS[i % DEAL_PRODUCTS.length]);
  grid.innerHTML = items.map((p, i) => `<div class="h-full">${dealCardInner(p, i)}</div>`).join("");
}

function mountFlashStack() {
  const stack = document.getElementById("flashStack");
  if (!stack) return;
  const items = [DEAL_PRODUCTS[2], DEAL_PRODUCTS[0], DEAL_PRODUCTS[1]];
  const layouts = [
    { pos: "left-[10px] top-[150px] lg:left-0 lg:top-[80px] rotate-[-8deg] w-[130px] lg:w-[230px] z-10", discount: DISCOUNT_STYLES[0] },
    { pos: "left-[65px] top-0 lg:left-[150px] lg:top-0 w-[150px] lg:w-[270px] z-20", discount: DISCOUNT_STYLES[2] },
    { pos: "right-[10px] top-[150px] lg:right-0 lg:top-[60px] rotate-[8deg] w-[125px] lg:w-[225px] z-10", discount: DISCOUNT_STYLES[1] },
  ];
  stack.innerHTML = items
    .map((p, i) => {
      const l = layouts[i];
      return `
      <div class="${l.pos} rounded-[6px] overflow-hidden shadow-xl" style="background:var(--color-white)">
        <div class="relative h-[110px] lg:h-[200px] w-full" style="background:var(--color-card-image-bg)">
          ${placeholderBox()}
          <span class="absolute top-0 left-0 flex items-center gap-[3px] rounded-br-[8px] px-[6px] py-[3px] text-white text-[9px] lg:text-[11px] font-medium whitespace-nowrap" style="background:var(--color-brand-green)">🔥 Trending</span>
          <button type="button" aria-label="Add to favorites" class="absolute top-[5px] right-[5px] bg-white rounded-full p-[5px] shadow cursor-pointer"><img src="assets/icons/icon-heart.svg" class="size-[13px]" alt="" /></button>
        </div>
        <div class="p-[8px] flex flex-col gap-[4px]">
          <p class="font-medium text-[11px] lg:text-[15px] truncate" style="color:var(--color-text-primary)">${p.name}</p>
          <div class="flex items-end gap-[5px]">
            <p class="font-bold text-[13px] lg:text-[18px]" style="color:var(--color-brand-green)">${p.price}</p>
            <p class="text-[9px] lg:text-[11px] line-through" style="color:var(--color-gray)">${p.oldPrice}</p>
          </div>
          <span class="inline-flex items-center justify-center px-[6px] py-[2px] self-start text-[8px] lg:text-[10px]" style="background:${l.discount.bg}; color:${l.discount.text}">${l.discount.label}</span>
        </div>
      </div>`;
    })
    .join("");
}

function initCarousels() {
  mountCarousel({ wrapperId: "newInWrapper", products: DEAL_PRODUCTS, prevId: "newInPrev", nextId: "newInNext" });
  mountBestSeller();
  mountTrending();
  mountRecommendedGrid();
  mountFlashStack();

  // Flash-sale countdown (starts from the design's captured 03:06:25 and ticks down)
  let remaining = 3 * 3600 + 6 * 60 + 25;
  const hEl = document.getElementById("flashHours");
  const mEl = document.getElementById("flashMinutes");
  const sEl = document.getElementById("flashSeconds");
  if (hEl && mEl && sEl) {
    setInterval(() => {
      remaining = Math.max(0, remaining - 1);
      hEl.textContent = String(Math.floor(remaining / 3600)).padStart(2, "0");
      mEl.textContent = String(Math.floor((remaining % 3600) / 60)).padStart(2, "0");
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
