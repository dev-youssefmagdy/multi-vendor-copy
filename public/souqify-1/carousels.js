// Product card rendering + Swiper carousel wiring for the Souqify home screen.
// Big "Deal Card" markup mirrors the Figma "Deal Card" component (node 2019:22654).
// Compact "Mini Card" markup mirrors the Figma "Mobile Card" component (node 2019:23015).
// Per client instruction, product-card thumbnails render as a neutral placeholder
// (no product photography fetched) instead of real images.

function placeholderIcon(sizeClasses) {
  return `<svg viewBox="0 0 24 24" fill="none" stroke="var(--color-stroke)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="${sizeClasses}">
    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
    <circle cx="8.5" cy="10" r="1.5"></circle>
    <path d="M21 16l-5.2-5.2a2 2 0 0 0-2.8 0L4.5 19.5"></path>
  </svg>`;
}

const PRODUCTS = {
  flashSale: [
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "35% off",
      discountBg: "var(--color-badge-discount-yellow)",
      discountColor: "var(--color-text-primary)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "25% off",
      discountBg: "var(--color-badge-discount-orange)",
      discountColor: "var(--color-bg-main)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "50% off",
      discountBg: "var(--color-error)",
      discountColor: "var(--color-bg-main)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "35% off",
      discountBg: "var(--color-badge-discount-yellow)",
      discountColor: "var(--color-text-primary)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "25% off",
      discountBg: "var(--color-badge-discount-orange)",
      discountColor: "var(--color-bg-main)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "50% off",
      discountBg: "var(--color-error)",
      discountColor: "var(--color-bg-main)",
    },
  ],
  trending: [
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "35% off",
      discountBg: "var(--color-badge-discount-yellow)",
      discountColor: "var(--color-text-primary)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "25% off",
      discountBg: "var(--color-badge-discount-orange)",
      discountColor: "var(--color-bg-main)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "50% off",
      discountBg: "var(--color-error)",
      discountColor: "var(--color-bg-main)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "35% off",
      discountBg: "var(--color-badge-discount-yellow)",
      discountColor: "var(--color-text-primary)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "25% off",
      discountBg: "var(--color-badge-discount-orange)",
      discountColor: "var(--color-bg-main)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "50% off",
      discountBg: "var(--color-error)",
      discountColor: "var(--color-bg-main)",
    },
  ],
  recommended: [
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "35% off",
      discountBg: "var(--color-badge-discount-yellow)",
      discountColor: "var(--color-text-primary)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "25% off",
      discountBg: "var(--color-badge-discount-orange)",
      discountColor: "var(--color-bg-main)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "50% off",
      discountBg: "var(--color-error)",
      discountColor: "var(--color-bg-main)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "35% off",
      discountBg: "var(--color-badge-discount-yellow)",
      discountColor: "var(--color-text-primary)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "25% off",
      discountBg: "var(--color-badge-discount-orange)",
      discountColor: "var(--color-bg-main)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "50% off",
      discountBg: "var(--color-error)",
      discountColor: "var(--color-bg-main)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "35% off",
      discountBg: "var(--color-badge-discount-yellow)",
      discountColor: "var(--color-text-primary)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "25% off",
      discountBg: "var(--color-badge-discount-orange)",
      discountColor: "var(--color-bg-main)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "50% off",
      discountBg: "var(--color-error)",
      discountColor: "var(--color-bg-main)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "35% off",
      discountBg: "var(--color-badge-discount-yellow)",
      discountColor: "var(--color-text-primary)",
    },
  ],
  newIn: [
    {
      name: "Essential Shoes",
      desc: "Premium cotton blend",
      weight: "250g",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
    {
      name: "Essential Shoes",
      desc: "Premium cotton blend",
      weight: "250g",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
    {
      name: "Essential Shoes",
      desc: "Premium cotton blend",
      weight: "250g",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
    {
      name: "Essential Shoes",
      desc: "Premium cotton blend",
      weight: "250g",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
    {
      name: "Essential Shoes",
      desc: "Premium cotton blend",
      weight: "250g",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
    {
      name: "Essential Shoes",
      desc: "Premium cotton blend",
      weight: "250g",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
    {
      name: "Essential Shoes",
      desc: "Premium cotton blend",
      weight: "250g",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
    {
      name: "Essential Shoes",
      desc: "Premium cotton blend",
      weight: "250g",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
    {
      name: "Essential Shoes",
      desc: "Premium cotton blend",
      weight: "250g",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
    {
      name: "Essential Shoes",
      desc: "Premium cotton blend",
      weight: "250g",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
    {
      name: "Essential Shoes",
      desc: "Premium cotton blend",
      weight: "250g",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
  ],
  bestSellerMini: [
    {
      name: "Essential Shoes",
      desc: "Premium cotton blend",
      weight: "250g",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
    {
      name: "Essential Shoes",
      desc: "Premium cotton blend",
      weight: "250g",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
    {
      name: "Essential Shoes",
      desc: "Premium cotton blend",
      weight: "250g",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
    {
      name: "Essential Shoes",
      desc: "Premium cotton blend",
      weight: "250g",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
    {
      name: "Essential Shoes",
      desc: "Premium cotton blend",
      weight: "250g",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
    {
      name: "Essential Shoes",
      desc: "Premium cotton blend",
      weight: "250g",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
  ],
  bestSellerBig: [
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "35% off",
      discountBg: "var(--color-badge-discount-yellow)",
      discountColor: "var(--color-text-primary)",
    },
    {
      name: "SonicFlow Wireless Studio Headphones Gen 2",
      weight: "200g",
      rating: "4.2 (+850)",
      price: "$149.00",
      oldPrice: "$199.00",
      discount: "35% off",
      discountBg: "var(--color-badge-discount-yellow)",
      discountColor: "var(--color-text-primary)",
    },
  ],
};

function renderDealCardInner(p) {
  return `
      <div class="flex flex-col items-start rounded-[8px] h-full overflow-hidden" style="background:var(--color-bg-main)">
        <div class="relative w-full shrink-0">
          <div class="product-img-placeholder w-full h-[183px] lg:h-[277px]" style="background:var(--color-card-bg-soft)">
            ${placeholderIcon("size-[54px] lg:size-[76px]")}
          </div>
          <button type="button" aria-label="Add to favorites" class="absolute top-[7px] right-[7px] lg:top-[10px] lg:right-[10px] bg-white rounded-full p-[9px] lg:p-[13px] shadow cursor-pointer">
            <img src="assets/icons/icon-heart-outline.svg" class="size-[15px] lg:size-[22px]" alt="" />
          </button>
          <div class="absolute bottom-[8px] right-[8px] lg:bottom-[14px] lg:right-[14px] bg-white rounded-[5px] p-[8px] lg:p-[11px] shadow flex items-center justify-center">
            <img src="assets/icons/icon-cart-add.svg" class="size-[22px] lg:size-[33px]" alt="Add to cart" />
          </div>
          <div class="absolute top-0 left-0 flex items-center h-[18px] lg:h-[26px]">
            <img src="assets/icons/ribbon-ticket.svg" class="absolute inset-0 h-full w-auto" alt="" />
            <span class="relative pl-[8px] lg:pl-[12px] pr-[16px] lg:pr-[22px] text-white text-[9px] lg:text-[12px] font-medium tracking-[0.5px] whitespace-nowrap">🔥 Trending Now</span>
          </div>
          <div class="urgency-ribbon absolute left-0 bottom-0 rounded-tr-[16px] lg:rounded-tr-[22px] px-[7px] py-[4px] lg:px-[9px] lg:py-[5px] flex flex-col gap-[3px] lg:gap-[4px]">
            <div class="flex items-center gap-[5px] lg:gap-[7px]">
              <img src="assets/icons/icon-truck-mini.svg" class="size-[13px] lg:size-[17px]" alt="" />
              <span class="text-[9px] lg:text-[12px] font-medium whitespace-nowrap" style="color:var(--color-black)">Delivered by 24 March</span>
            </div>
            <div class="flex items-center gap-[3px]">
              <img src="assets/icons/icon-cart-x.svg" class="size-[9px] lg:size-[12px]" alt="" />
              <span class="text-white text-[8px] lg:text-[10px] font-medium whitespace-nowrap">Only 5 left - Hurry up</span>
            </div>
          </div>
        </div>
        <div class="flex flex-col gap-[5px] lg:gap-[8px] p-[5px] lg:p-[8px] w-full">
          <div class="flex items-start justify-between gap-[4px]">
            <p class="text-[14px] lg:text-[17px] font-medium truncate" style="color:var(--color-text-heading-alt)">${p.name}</p>
            <p class="text-[12px] lg:text-[15px] font-semibold shrink-0" style="color:var(--color-primary)">${p.weight}</p>
          </div>
          <div class="flex items-center gap-[6px]">
            <img src="assets/icons/icon-star-rating.svg" class="h-[8px] lg:h-[10px] w-[58px] lg:w-[70px]" alt="" />
            <span class="text-[12px] lg:text-[15px]" style="color:var(--color-text-subtitle)">${p.rating}</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-end gap-[5px]">
              <p class="text-[16px] lg:text-[20px] font-bold" style="color:var(--color-brand-purple)">${p.price}</p>
              <p class="text-[10px] lg:text-[12px] line-through" style="color:var(--color-gray)">${p.oldPrice}</p>
            </div>
            <div class="flex items-center justify-center px-[5px] py-[4px] lg:px-[6px] lg:py-[5px]" style="background:${p.discountBg}">
              <span class="text-[9px] lg:text-[11px] tracking-[0.5px] whitespace-nowrap" style="color:${p.discountColor}">${p.discount}</span>
            </div>
          </div>
        </div>
      </div>`;
}

function renderDealCard(p) {
  return `<div class="swiper-slide h-auto !w-[210px] lg:!w-[242px]">${renderDealCardInner(p)}</div>`;
}

// Recommended For You is a static multi-row grid, not a carousel (node 2019:23164 —
// 5 columns of Deal Cards repeating across several rows), so each item is a plain grid
// cell instead of a swiper-slide.
function renderDealCardGridItem(p) {
  return `<div class="h-[360px] lg:h-[401px]">${renderDealCardInner(p)}</div>`;
}

function renderMiniCard(p) {
  return `
    <div class="flex gap-[4px] rounded-[8px] overflow-hidden h-[110px] lg:h-[127px] w-full" style="background:var(--color-bg-main)">
      <div class="product-img-placeholder relative w-[152px] lg:w-[174px] shrink-0 rounded-tl-[7px]" style="background:var(--color-page-bg)">
        ${placeholderIcon("size-[38px] lg:size-[44px]")}
        <span class="absolute top-0 left-0 text-[9px] lg:text-[11px] px-[6px] py-[3px] rounded-br-[6px] rounded-tl-[7px] tracking-[0.3px]" style="background:var(--color-accent-yellow); color:var(--color-black)">70% Sold</span>
        <button type="button" aria-label="Add to favorites" class="absolute top-[5px] right-[5px] bg-white rounded-full p-[5px] shadow cursor-pointer">
          <img src="assets/icons/icon-heart-outline.svg" class="size-[13px]" alt="" />
        </button>
        <div class="absolute bottom-[4px] right-[4px] bg-white rounded-[12px] p-[6px] shadow flex items-center justify-center">
          <img src="assets/icons/icon-cart-add.svg" class="size-[15px]" alt="Add to cart" />
        </div>
      </div>
      <div class="flex-1 flex flex-col gap-[4px] p-[5px] min-w-0">
        <div class="flex items-center justify-between gap-[4px]">
          <p class="text-[14px] lg:text-[16px] font-medium truncate" style="color:var(--color-text-primary)">${p.name}</p>
          <p class="text-[12px] lg:text-[13px] shrink-0" style="color:var(--color-brand-purple)">${p.weight}</p>
        </div>
        <p class="text-[12px] lg:text-[13px] truncate" style="color:var(--color-text-subtitle)">${p.desc}</p>
        <div class="flex items-center gap-[5px]">
          <img src="assets/icons/icon-star-rating.svg" class="h-[8px] w-[54px]" alt="" />
          <span class="text-[10px] lg:text-[11px]" style="color:var(--color-text-subtitle)">${p.rating}</span>
        </div>
        <div class="flex items-end gap-[5px] flex-wrap">
          <p class="text-[14px] lg:text-[16px] font-medium" style="color:var(--color-brand-purple)">${p.price}</p>
          <p class="text-[10px] lg:text-[11px] line-through" style="color:var(--color-text-subtitle)">${p.oldPrice}</p>
          <p class="text-[10px] lg:text-[11px]" style="color:var(--color-secondary)">${p.discount}</p>
        </div>
        <div class="flex items-center gap-[4px]">
          <img src="assets/icons/icon-truck-mini.svg" class="size-[13px]" alt="" />
          <span class="text-[10px] lg:text-[11px] font-medium truncate" style="color:var(--color-success)">Delivered by 24 March</span>
        </div>
      </div>
    </div>`;
}

function renderMiniStackSlide(products) {
  return `
    <div class="swiper-slide h-auto !w-[300px] lg:!w-[402px]">
      <div class="flex flex-col gap-[10px] lg:gap-[11px] h-full">
        ${products.map(renderMiniCard).join("")}
      </div>
    </div>`;
}

function mountCarousel({ wrapperId, slidesHtml, prevId, nextId, extra }) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;
  wrapper.innerHTML = slidesHtml;

  return new Swiper(
    wrapper.closest(".swiper"),
    Object.assign(
      {
        slidesPerView: "auto",
        spaceBetween: 16,
        navigation: {
          prevEl: prevId ? `#${prevId}` : undefined,
          nextEl: nextId ? `#${nextId}` : undefined,
        },
      },
      extra || {},
    ),
  );
}

function initCarousels() {
  mountCarousel({
    wrapperId: "flashSaleWrapper",
    slidesHtml: PRODUCTS.flashSale.map(renderDealCard).join(""),
    prevId: "flashSalePrev",
    nextId: "flashSaleNext",
  });

  // New In is a 3-row grid (not a single-row carousel) — plain CSS grid instead of
  // Swiper, since Swiper's Grid module produced uneven row heights here. Prev/Next
  // just scroll the grid horizontally by one column; they sit outside the grid
  // (siblings, not grid children) so they never get placed as a cell in it.
  const newInGrid = document.getElementById("newInWrapper");
  if (newInGrid) {
    newInGrid.innerHTML = PRODUCTS.newIn.map(renderMiniCard).join("");
    const newInPrev = document.getElementById("newInPrev");
    const newInNext = document.getElementById("newInNext");
    const scrollNewIn = (dir) => {
      const colWidth = newInGrid.firstElementChild.getBoundingClientRect().width + 12;
      newInGrid.scrollBy({ left: dir * colWidth, behavior: "smooth" });
    };
    if (newInPrev) newInPrev.addEventListener("click", () => scrollNewIn(-1));
    if (newInNext) newInNext.addEventListener("click", () => scrollNewIn(1));
  }

  mountCarousel({
    wrapperId: "trendingWrapper",
    slidesHtml: PRODUCTS.trending.map(renderDealCard).join(""),
    prevId: "trendingPrev",
    nextId: "trendingNext",
  });

  // Best Seller alternates a big Deal Card slide with a 3-up mini-card stack slide.
  const bestSellerSlides = [
    renderDealCard(PRODUCTS.bestSellerBig[0]),
    renderMiniStackSlide(PRODUCTS.bestSellerMini.slice(0, 3)),
    renderDealCard(PRODUCTS.bestSellerBig[1]),
    renderMiniStackSlide(PRODUCTS.bestSellerMini.slice(3, 6)),
  ].join("");
  mountCarousel({
    wrapperId: "bestSellerWrapper",
    slidesHtml: bestSellerSlides,
    prevId: "bestSellerPrev",
    nextId: "bestSellerNext",
  });

  const recommendedGrid = document.getElementById("recommendedWrapper");
  if (recommendedGrid) {
    recommendedGrid.innerHTML = PRODUCTS.recommended
      .map(renderDealCardGridItem)
      .join("");
  }

  // Flash-sale countdown (starts from the design's captured 03:06:25 and ticks down)
  let remaining = 3 * 3600 + 6 * 60 + 25;
  const timerEls = document.querySelectorAll("[data-flash-timer]");
  if (timerEls.length) {
    setInterval(() => {
      remaining = Math.max(0, remaining - 1);
      const h = String(Math.floor(remaining / 3600)).padStart(2, "0");
      const m = String(Math.floor((remaining % 3600) / 60)).padStart(2, "0");
      const s = String(remaining % 60).padStart(2, "0");
      timerEls.forEach((el) => {
        const which = el.getAttribute("data-flash-timer");
        el.textContent = which === "h" ? h : which === "m" ? m : s;
      });
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
