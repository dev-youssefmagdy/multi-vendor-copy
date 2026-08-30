// Product card rendering + Swiper carousel wiring for the ELORA "Home" screen (Theme #2).
// Card markup mirrors the Figma "Mobile Card" component (node 2007:7327 family).
// Product photography is a shared neutral placeholder (assets/images/product-placeholder.svg) for every
// product-card slot per project direction; all other imagery (hero, categories, tiles) is real exported art.

const PLACEHOLDER_IMG = "assets/images/product-placeholder.svg";

const PRODUCTS = {
  flashSale: [
    {
      name: "Essential Hoodie",
      weight: "200g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      stock: "Only 5 left",
    },
    {
      name: "Essential Shoes",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      stock: "Only 5 left",
    },
    {
      name: "Wireless Headphones",
      weight: "180g",
      price: "$79.00",
      oldPrice: "$99.00",
      discount: "20% Off",
      rating: "4.4 (+560)",
      stock: "Only 3 left",
    },
    {
      name: "Classic Sneakers",
      weight: "260g",
      price: "$110.00",
      oldPrice: "$130.00",
      discount: "15% Off",
      rating: "4.6 (+1.4k)",
      stock: "Only 5 left",
    },
    {
      name: "Wireless Mouse",
      weight: "120g",
      price: "$39.00",
      oldPrice: "$49.00",
      discount: "20% Off",
      rating: "4.1 (+430)",
      stock: "Only 8 left",
    },
    {
      name: "Graphic Tee",
      weight: "150g",
      price: "$45.00",
      oldPrice: "$55.00",
      discount: "18% Off",
      rating: "4.3 (+390)",
      stock: "Only 6 left",
    },
  ],
  newIn: [
    {
      name: "Essential Shoes",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      ordered: "5 ordered last 30 min",
      progress: "83%",
      stock: "Only 5 left",
    },
    {
      name: "Court Sneakers",
      weight: "255g",
      price: "$92.00",
      oldPrice: "$110.00",
      discount: "16% Off",
      rating: "4.3 (+390)",
      ordered: "8 ordered last 30 min",
      progress: "64%",
      stock: "Only 4 left",
    },
    {
      name: "Wireless Earbuds",
      weight: "60g",
      price: "$59.00",
      oldPrice: "$79.00",
      discount: "25% Off",
      rating: "4.5 (+920)",
      ordered: "12 ordered last 30 min",
      progress: "72%",
      stock: "Only 7 left",
    },
    {
      name: "Classic Watch",
      weight: "90g",
      price: "$149.00",
      oldPrice: "$179.00",
      discount: "17% Off",
      rating: "4.6 (+1.2k)",
      ordered: "3 ordered last 30 min",
      progress: "40%",
      stock: "Only 9 left",
    },
  ],
  trending: [
    {
      name: "Essential Hoodie",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      stock: "Only 5 left",
    },
    {
      name: "Essential Hoodie",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      stock: "Only 5 left",
    },
    {
      name: "Retro Sneakers",
      weight: "260g",
      price: "$99.00",
      oldPrice: "$120.00",
      discount: "18% Off",
      rating: "4.4 (+610)",
      stock: "Only 6 left",
    },
    {
      name: "Studio Headphones",
      weight: "190g",
      price: "$99.00",
      oldPrice: "$119.00",
      discount: "17% Off",
      rating: "4.3 (+700)",
      stock: "Only 3 left",
    },
    {
      name: "Court Sneakers",
      weight: "270g",
      price: "$92.00",
      oldPrice: null,
      discount: null,
      rating: "4.3 (+390)",
      stock: "Only 4 left",
    },
    {
      name: "Wireless Mouse",
      weight: "120g",
      price: "$39.00",
      oldPrice: "$49.00",
      discount: "20% Off",
      rating: "4.1 (+430)",
      stock: "Only 8 left",
    },
  ],
  bestSellerDesktop: [
    // Back-to-front stacking order (matches the Figma fan: smallest/rightmost is drawn first, largest/frontmost last)
    {
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      alt: true,
      stock: "Only 5 left",
    },
    {
      name: "Essential Hoodie",
      weight: "200g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Essential Shoes",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Essential Hoodie",
      weight: "200g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Essential Shoes",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      alt: true,
      stock: "Only 5 left",
    },
  ],
  bestSellerMobile: [
    {
      name: "Essential Shoes",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Essential Hoodie",
      weight: "200g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      alt: true,
      stock: "Only 5 left",
    },
  ],
  recommended: [
    {
      name: "Zip Hoodie",
      weight: "215g",
      price: "$97.00",
      oldPrice: "$115.00",
      discount: "15% Off",
      rating: "4.4 (+640)",
      stock: "Only 5 left",
    },
    {
      name: "Street Sneakers",
      weight: "265g",
      price: "$105.00",
      oldPrice: "$125.00",
      discount: "16% Off",
      rating: "4.2 (+505)",
      stock: "Only 4 left",
    },
    {
      name: "Over-ear Headphones",
      weight: "185g",
      price: "$85.00",
      oldPrice: "$99.00",
      discount: "14% Off",
      rating: "4.3 (+700)",
      stock: "Only 6 left",
    },
    {
      name: "Chrono Watch",
      weight: "95g",
      price: "$159.00",
      oldPrice: "$189.00",
      discount: "16% Off",
      rating: "4.7 (+890)",
      stock: "Only 3 left",
    },
    {
      name: "Ergo Mouse",
      weight: "115g",
      price: "$35.00",
      oldPrice: "$45.00",
      discount: "22% Off",
      rating: "4.0 (+210)",
      stock: "Only 9 left",
    },
    {
      name: "Pullover Hoodie",
      weight: "225g",
      price: "$91.00",
      oldPrice: "$110.00",
      discount: "17% Off",
      rating: "4.6 (+890)",
      stock: "Only 5 left",
    },
    {
      name: "Trail Sneakers",
      weight: "270g",
      price: "$115.00",
      oldPrice: "$135.00",
      discount: "15% Off",
      rating: "4.4 (+560)",
      stock: "Only 5 left",
    },
    {
      name: "Fleece Hoodie",
      weight: "220g",
      price: "$99.00",
      oldPrice: "$120.00",
      discount: "18% Off",
      rating: "4.3 (+710)",
      stock: "Only 5 left",
    },
  ],
};

const CATEGORIES = [
  { name: "Women bags", image: "assets/images/cat-bag.png" },
  { name: "Accessories", image: "assets/images/cat-accessories.png" },
  { name: "Gaming", image: "assets/images/cat-gaming.png" },
  { name: "Electronics", image: "assets/images/cat-electronics.png" },
  { name: "Women bags", image: "assets/images/cat-bag.png" },
  { name: "Accessories", image: "assets/images/cat-accessories.png" },
  { name: "Gaming", image: "assets/images/cat-gaming.png" },
  { name: "Electronics", image: "assets/images/cat-electronics.png" },
];

const FEATURES = [
  {
    icon: "assets/icons/icon-feature-truck.svg",
    title: "Free Shipping",
    subtitle: "Free shipping on all your order",
  },
  {
    icon: "assets/icons/icon-feature-headphones.svg",
    title: "Customer Support 24/7",
    subtitle: "Instant access to Support",
  },
  {
    icon: "assets/icons/icon-feature-bag.svg",
    title: "100% Secure Payment",
    subtitle: "We ensure your money is save",
  },
  {
    icon: "assets/icons/icon-feature-package.svg",
    title: "Money-Back Guarantee",
    subtitle: "30 Days Money-Back Guarantee",
  },
];

function renderCategory(c) {
  return `
    <div class="flex flex-col items-center gap-[6px] shrink-0 w-[80px] lg:w-[150px]">
      <div class="category-blob relative flex items-center justify-center w-full h-[64px] lg:h-[120px]">
        <img src="${c.image}" alt="${c.name}" class="h-[42px] lg:h-[78px] w-auto object-contain" />
      </div>
      <p class="font-semibold text-[12px] lg:text-[20px] tracking-[0.3px] text-center whitespace-nowrap" style="color:var(--color-black)">${c.name}</p>
    </div>`;
}

function renderCardBadge(p) {
  if (p.alt) {
    return `<div class="flex items-center justify-center px-[10px] py-[5px] rounded-bl-[8px] rounded-tr-[6px] shrink-0" style="background:var(--color-badge-orange)"><p class="font-normal text-[11px] lg:text-[14px] tracking-[0.3px] whitespace-nowrap text-white">30% OFF</p></div>`;
  }
  return `<div class="flex items-center justify-center px-[10px] py-[5px] rounded-bl-[8px] rounded-tr-[6px] shrink-0" style="background:var(--color-yellow)"><p class="font-normal text-[11px] lg:text-[14px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-black-alt)">70% Sold</p></div>`;
}

// Vertical "big" card — shared visual for Flash Sale / Best Seller / Recommended For You
function renderProductCardInner(p, imgHeight) {
  const priceColor = p.alt
    ? "var(--color-badge-orange)"
    : "var(--color-price-blue)";
  // imgHeight lets callers (Flash Sale / Best Seller) scale the image band relative to
  // the card's own height instead of the fixed mobile/desktop breakpoint sizes used
  // elsewhere. Accepts a number (px) or a ready-made CSS length string (e.g. "55%").
  // A percentage is applied ONLY on the outermost image-band div — the three nested
  // divs inside it get 100% instead, since they're percentage-sized against THAT div
  // (their containing block), not the card: reusing the same "55%" on all four would
  // compound down to ~55% of 55% of 55%, not a flat 55% of the card.
  const imgH =
    imgHeight == null
      ? null
      : typeof imgHeight === "number"
        ? `${imgHeight}px`
        : imgHeight;
  const isPercent = typeof imgH === "string" && imgH.endsWith("%");
  const imgHClass = imgH ? "" : "h-[130px] lg:h-[190px]";
  const imgHStyleOuter = imgH ? `height:${imgH};` : "";
  const imgHStyleInner = imgH
    ? isPercent
      ? "height:100%;"
      : imgHStyleOuter
    : "";
  return `
      <div class="flex flex-col items-start rounded-[8px] h-full shadow-[var(--shadow-card-lg)]" style="background:var(--color-bg-main)">
        <div class="js-fan-imgband flex flex-col gap-[4px] lg:gap-[6px] ${imgHClass} items-end justify-end px-[5px] lg:px-[7px] py-[4px] lg:py-[5px] relative shrink-0 w-full" style="${imgHStyleOuter}">
          <div class="js-fan-imgband absolute flex gap-[4px] lg:gap-[6px] ${imgHClass} items-start left-0 p-[5px] lg:p-[7px] top-0 w-full" style="${imgHStyleInner}">
            <div class="js-fan-imgband absolute flex flex-col gap-[4px] lg:gap-[6px] ${imgHClass} items-end left-0 top-0 w-full" style="${imgHStyleInner}">
              <div class="js-fan-imgband absolute left-1/2 -translate-x-1/2 ${imgHClass} rounded-t-[6px] lg:rounded-t-[8px] top-0 w-full overflow-hidden" style="${imgHStyleInner}">
                <img src="${PLACEHOLDER_IMG}" alt="${p.name}" class="absolute inset-0 h-full w-full object-cover" />
              </div>
              ${renderCardBadge(p)}
            </div>
            <button type="button" aria-label="Add to favorites" class="bg-white cursor-pointer shadow flex items-center justify-center p-[5px] lg:p-[8px] relative rounded-full shrink-0 size-[22px] lg:size-[32px]">
              <img src="assets/icons/heart.svg" alt="" class="size-[13px] lg:size-[19px]" />
            </button>
          </div>
          <div class="flex items-center justify-center p-[7px] lg:p-[10px] relative rounded-full shrink-0 size-[34px] lg:size-[48px] bg-white shadow">
            <img src="assets/icons/icon-cart-card.svg" alt="Add to cart" class="size-[18px] lg:size-[26px]" />
          </div>
        </div>
        <div class="flex flex-col gap-[4px] lg:gap-[6px] items-start p-[6px] lg:p-[8px] relative shrink-0 w-full">
          <div class="flex flex-col gap-[2px] items-start w-full">
            <div class="flex items-center justify-between gap-[6px] w-full whitespace-nowrap">
              <p class="font-medium text-[13px] lg:text-[19px] truncate" style="color:var(--color-black)">${p.name}</p>
              <p class="font-normal text-[11px] lg:text-[15px] shrink-0" style="color:${priceColor}">${p.weight}</p>
            </div>
            <p class="font-normal text-[10px] lg:text-[15px] w-full truncate" style="color:var(--color-subtitle)">Premium cotton blend</p>
          </div>
          <div class="flex flex-col gap-[2px] items-start">
            <div class="flex gap-[5px] lg:gap-[8px] items-center justify-center">
              <img src="assets/icons/star-rating.svg" alt="" class="h-[7px] lg:h-[10px] w-[48px] lg:w-[68px]" />
              <span class="text-[9px] lg:text-[12px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-subtitle)">${p.rating}</span>
            </div>
            <div class="flex gap-[5px] lg:gap-[8px] items-end flex-wrap">
              <p class="font-medium text-[13px] lg:text-[19px] whitespace-nowrap" style="color:var(--color-black)">${p.price}</p>
              <p class="font-light text-[9px] lg:text-[12px] line-through whitespace-nowrap" style="color:var(--color-subtitle)">${p.oldPrice}</p>
              <p class="font-normal text-[9px] lg:text-[12px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-secondary)">${p.discount}</p>
            </div>
          </div>
          <div class="flex flex-col gap-[2px] items-start w-full">
            <div class="flex gap-[5px] lg:gap-[7px] items-center w-full">
              <img src="assets/icons/icon-truck-small.svg" alt="" class="size-[12px] lg:size-[16px] shrink-0" />
              <p class="font-medium text-[9px] lg:text-[12px] whitespace-nowrap truncate" style="color:${p.alt ? "var(--color-error)" : "var(--color-success)"}">Delivered by 24 March</p>
            </div>
            ${
              p.stock
                ? `<div class="flex gap-[5px] lg:gap-[8px] items-center">
              <img src="assets/icons/cart-x.svg" alt="" class="size-[12px] lg:size-[15px] shrink-0" />
              <p class="font-medium text-[9px] lg:text-[11px] whitespace-nowrap" style="color:${p.alt ? "var(--color-error)" : "var(--color-success)"}">${p.stock}</p>
            </div>`
                : ""
            }
          </div>
        </div>
      </div>`;
}

function renderProductCard(p) {
  return `<div class="swiper-slide h-auto !w-[150px] lg:!w-[220px]">${renderProductCardInner(p)}</div>`;
}

// Best Seller: draggable fan cascade — geometry captured from the Figma card
// stack (node 2007:7743 / 2007:5559), indexed by DISTANCE from the swiper's
// activeIndex (0 = leftmost/biggest ... down to smallest/rightmost), not by
// a fixed product index. Every slide is absolutely positioned from this
// table each time activeIndex changes, so dragging shifts which product
// lands in the "distance 0" (biggest) slot while the rest cascade down —
// matching the original static fan's exact pixel geometry, but now live.
const BEST_SELLER_POSITIONS_DESKTOP = [
  { left: 0, top: 0, width: 284, height: 430 },
  { left: 230, top: 39, width: 256, height: 388 },
  { left: 440, top: 57, width: 244, height: 370 },
  { left: 630, top: 79, width: 230, height: 348 },
  { left: 811, top: 102, width: 215, height: 326 },
  { left: 980, top: 123, width: 203, height: 307 },
];
const BEST_SELLER_POSITIONS_MOBILE = [
  { left: 0, top: 0, width: 172, height: 262 },
  { left: 130, top: 35, width: 141, height: 213 },
  { left: 224, top: 58, width: 119, height: 180 },
];

function renderBestSellerSlide(p) {
  return `
    <div class="swiper-slide bestseller-slide" style="position:absolute; top:0; left:0;">
      ${renderProductCardInner(p, "55%")}
    </div>`;
}

function mountBestSellerCarousel(containerId, products, positions) {
  const container = document.getElementById(containerId);
  if (!container) return;
  container.innerHTML = products.map(renderBestSellerSlide).join("");
  container.style.position = "relative";
  container.style.height = `${positions[0].top + positions[0].height}px`;

  // Only the ORIGINAL slides (captured before Swiper's loop mode injects its
  // own clone slides) get positioned here — clones are neutralized separately
  // below, since our own modulo wrap already makes the cascade infinite
  // without needing Swiper's duplicated DOM nodes to be visually meaningful.
  const slideEls = [...container.children];
  const count = products.length;
  const applyPositions = (sw) => {
    slideEls.forEach((slideEl, i) => {
      const distance = (((i - sw.realIndex) % count) + count) % count;
      const box = positions[distance];
      if (!box) {
        // more products than fan slots: park the rest off-screen rather
        // than stacking them at position 0 on top of the active card.
        slideEl.style.opacity = "0";
        slideEl.style.pointerEvents = "none";
        slideEl.style.zIndex = "0";
        return;
      }
      slideEl.style.opacity = "1";
      slideEl.style.pointerEvents = "";
      slideEl.style.left = `${box.left}px`;
      slideEl.style.top = `${box.top}px`;
      slideEl.style.width = `${box.width}px`;
      slideEl.style.height = `${box.height}px`;
      slideEl.style.zIndex = String(positions.length - distance);
    });
    // Loop mode clones aren't part of slideEls and never get positioned —
    // hide them so they don't sit stacked at their default top:0/left:0.
    container.querySelectorAll(".swiper-slide-duplicate").forEach((clone) => {
      clone.style.opacity = "0";
      clone.style.pointerEvents = "none";
    });
  };

  return new Swiper(container.closest(".swiper"), {
    slidesPerView: "auto",
    virtualTranslate: true,
    loop: true,
    loopedSlides: count,
    on: {
      init: applyPositions,
      slideChange: applyPositions,
      transitionEnd: applyPositions,
      setTransition(sw, duration) {
        slideEls.forEach((s) => (s.style.transitionDuration = `${duration}ms`));
      },
    },
  });
}

// Wide horizontal card — New In (with order progress bar) / Trending Now
function renderWideCardInner(p) {
  return `
      <div class="flex gap-0 h-full items-stretch rounded-[10px] shadow-[var(--shadow-card-lg)] overflow-hidden" style="background:var(--color-bg-main)">
        <div class="flex flex-col gap-[7px] items-end justify-end px-[5px] py-[4px] relative shrink-0 w-[42%]">
          <div class="absolute flex gap-[7px] h-full items-start left-0 p-[5px] top-0 w-full">
            <div class="absolute flex flex-col gap-[7px] h-full items-end left-0 top-0 w-full">
              <div class="absolute inset-0 overflow-hidden">
                <img src="${PLACEHOLDER_IMG}" alt="${p.name}" class="absolute inset-0 h-full w-full object-cover" />
              </div>
              <div class="relative flex items-center justify-center px-[8px] py-[4px] rounded-bl-[6px] rounded-tr-[6px] shrink-0" style="background:var(--color-yellow)">
                <p class="font-normal text-[10px] lg:text-[12px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-black-alt)">70% Sold</p>
              </div>
            </div>
            <button type="button" aria-label="Add to favorites" class="relative bg-white cursor-pointer shadow flex items-center justify-center p-[6px] rounded-full shrink-0 size-[26px] lg:size-[32px]">
              <img src="assets/icons/heart.svg" alt="" class="size-[15px] lg:size-[19px]" />
            </button>
          </div>
          <div class="relative flex items-center justify-center p-[7px] rounded-full shrink-0 size-[32px] lg:size-[38px] bg-white shadow">
            <img src="assets/icons/icon-cart-card.svg" alt="Add to cart" class="size-[16px] lg:size-[20px]" />
          </div>
        </div>
        <div class="flex flex-1 flex-col gap-[6px] p-[10px] lg:p-[12px] items-start min-w-0 justify-center">
          <div class="flex flex-col gap-[3px] items-start w-full">
            <div class="flex items-center justify-between w-full whitespace-nowrap">
              <p class="font-medium text-[14px] lg:text-[18px]" style="color:var(--color-black)">${p.name}</p>
              <p class="font-normal text-[12px] lg:text-[15px]" style="color:var(--color-price-blue)">${p.weight}</p>
            </div>
            <p class="font-normal text-[11px] lg:text-[14px] w-full" style="color:var(--color-subtitle)">Premium cotton blend</p>
          </div>
          ${
            p.ordered
              ? `<div class="flex flex-col gap-[2px] items-start w-full">
            <div class="h-[6px] w-full rounded-full" style="background:#D9D9D9">
              <div class="h-full rounded-full" style="background:var(--color-price-blue); width:${p.progress}"></div>
            </div>
            <p class="text-[10px] lg:text-[12px]" style="color:var(--color-price-blue)">${p.ordered}</p>
          </div>`
              : ""
          }
          <div class="flex gap-[5px] items-center">
            <img src="assets/icons/star-rating.svg" alt="" class="h-[8px] w-[54px]" />
            <span class="text-[10px] lg:text-[12px] whitespace-nowrap" style="color:var(--color-subtitle)">${p.rating}</span>
          </div>
          <div class="flex gap-[6px] items-end">
            <p class="font-medium text-[14px] lg:text-[18px] whitespace-nowrap" style="color:var(--color-black)">${p.price}</p>
            <p class="font-light text-[10px] lg:text-[12px] line-through whitespace-nowrap" style="color:var(--color-subtitle)">${p.oldPrice}</p>
            <p class="font-normal text-[10px] lg:text-[12px] whitespace-nowrap" style="color:var(--color-secondary)">${p.discount}</p>
          </div>
          <div class="flex flex-col gap-[3px] items-start w-full">
            <div class="flex gap-[5px] items-center w-full">
              <img src="assets/icons/icon-truck-small.svg" alt="" class="size-[13px] lg:size-[15px]" />
              <p class="font-medium text-[10px] lg:text-[12px] whitespace-nowrap" style="color:var(--color-success)">Delivered by 24 March</p>
            </div>
            <div class="flex gap-[5px] items-center">
              <img src="assets/icons/cart-x.svg" alt="" class="size-[12px] lg:size-[14px]" />
              <p class="font-medium text-[10px] lg:text-[12px] whitespace-nowrap" style="color:var(--color-success)">${p.stock}</p>
            </div>
          </div>
        </div>
      </div>`;
}

function renderWideCard(p) {
  return `<div class="swiper-slide h-auto !w-[280px] lg:!w-[430px]">${renderWideCardInner(p)}</div>`;
}

// Flash Sale carousel mixes two slide types (node 2007:7318): full-size vertical product
// cards, and a single "combo" slide bundling the countdown timer with two stacked mini
// horizontal cards. The combo isn't a static header above the carousel — it's embedded
// as its own slide between the first and remaining product cards.
function renderFlashBigCard(p) {
  return `<div class="swiper-slide !w-[200px] lg:!w-[298px] !h-[302px] lg:!h-[450px]">${renderProductCardInner(p, "55%")}</div>`;
}

function renderFlashMiniCard(p) {
  return `
    <div class="flex gap-0 h-[110px] lg:h-[169px] items-stretch rounded-[10px] shadow-[var(--shadow-card-lg)] overflow-hidden w-full" style="background:var(--color-bg-main)">
      <div class="relative shrink-0 w-[42%]">
        <img src="${PLACEHOLDER_IMG}" alt="${p.name}" class="absolute inset-0 h-full w-full object-cover" />
        <div class="absolute top-0 left-0 flex items-center justify-center px-[8px] py-[4px] rounded-br-[6px]" style="background:var(--color-yellow)">
          <p class="font-normal text-[10px] lg:text-[12px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-black-alt)">70% Sold</p>
        </div>
        <button type="button" aria-label="Add to favorites" class="absolute top-[6px] right-[6px] bg-white cursor-pointer shadow flex items-center justify-center p-[6px] rounded-full">
          <img src="assets/icons/heart.svg" alt="" class="size-[13px] lg:size-[16px]" />
        </button>
      </div>
      <div class="flex flex-1 flex-col gap-[4px] p-[8px] lg:p-[10px] items-start min-w-0 justify-center">
        <div class="flex items-center justify-between w-full whitespace-nowrap">
          <p class="font-medium text-[13px] lg:text-[16px]" style="color:var(--color-black)">${p.name}</p>
          <p class="font-normal text-[11px] lg:text-[13px]" style="color:var(--color-price-blue)">${p.weight}</p>
        </div>
        <div class="flex gap-[5px] items-end">
          <p class="font-medium text-[13px] lg:text-[16px] whitespace-nowrap" style="color:var(--color-black)">${p.price}</p>
          <p class="font-light text-[10px] lg:text-[12px] line-through whitespace-nowrap" style="color:var(--color-subtitle)">${p.oldPrice}</p>
          <p class="font-normal text-[10px] lg:text-[12px] whitespace-nowrap" style="color:var(--color-secondary)">${p.discount}</p>
        </div>
        <div class="flex gap-[4px] items-center w-full">
          <img src="assets/icons/icon-truck-small.svg" alt="" class="size-[11px] lg:size-[13px]" />
          <p class="font-medium text-[9px] lg:text-[11px] whitespace-nowrap" style="color:var(--color-success)">Delivered by 24 March</p>
        </div>
      </div>
    </div>`;
}

function renderFlashCombo(miniProducts) {
  return `
    <div class="swiper-slide !w-[362px] lg:!w-[536px] !h-[302px] lg:!h-[450px]">
      <div class="flex flex-col gap-[10px] lg:gap-[12px] items-start h-full">
        <div class="flex items-center gap-[6px] lg:gap-[8px]">
          <span class="font-semibold text-[12px] lg:text-[16px] tracking-[0.6px] -rotate-90 w-0 whitespace-nowrap" style="color:var(--color-yellow)">Ends in</span>
          <div class="flex items-center justify-center h-[36px] w-[38px] lg:h-[52px] lg:w-[54px] rounded-[8px]" style="background:linear-gradient(180deg, #FFFFFF 51%, #E5E5E5 51%)">
            <span class="font-semibold text-[18px] lg:text-[26px]" style="color:var(--color-price-blue)" data-flash-timer>03</span>
          </div>
          <div class="flex items-center justify-center h-[36px] w-[38px] lg:h-[52px] lg:w-[54px] rounded-[8px]" style="background:linear-gradient(180deg, #FFFFFF 51%, #E5E5E5 51%)">
            <span class="font-semibold text-[18px] lg:text-[26px]" style="color:var(--color-price-blue)" data-flash-timer>06</span>
          </div>
          <div class="flex items-center justify-center h-[36px] w-[38px] lg:h-[52px] lg:w-[54px] rounded-[8px]" style="background:linear-gradient(180deg, #FFFFFF 51%, #E5E5E5 51%)">
            <span class="font-semibold text-[18px] lg:text-[26px]" style="color:var(--color-price-blue)" data-flash-timer>25</span>
          </div>
        </div>
        ${renderFlashMiniCard(miniProducts[0])}
        ${renderFlashMiniCard(miniProducts[1])}
      </div>
    </div>`;
}

function mountFlashSale() {
  const wrapper = document.getElementById("flashSaleWrapper");
  if (!wrapper) return;
  const p = PRODUCTS.flashSale;
  wrapper.innerHTML = [
    renderFlashBigCard(p[0]),
    renderFlashCombo([p[1], p[2]]),
    renderFlashBigCard(p[3]),
    renderFlashBigCard(p[4]),
    renderFlashBigCard(p[5]),
  ].join("");

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    navigation: { prevEl: "#flashSalePrev", nextEl: "#flashSaleNext" },
  });
}

function renderRecommendedGrid() {
  const grid = document.getElementById("recommendedWrapper");
  if (!grid) return;
  grid.innerHTML = PRODUCTS.recommended
    .map((p) => `<div class="h-full">${renderProductCardInner(p)}</div>`)
    .join("");
}

function mountCarousel({ wrapperId, products, renderer }) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;
  wrapper.innerHTML = products.map(renderer || renderProductCard).join("");

  // slidesPerView must stay "auto" here: every renderer used with this
  // function sets a fixed !w-[Npx] on its own swiper-slide, and that
  // permanently overrides whatever pixel width a numeric slidesPerView would
  // compute — the mismatch is what let the wrapper's real content width run
  // past the container and overflow the page.
  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    // trailing space after the last slide so it doesn't sit flush against
    // the section's edge — there are no nav buttons here to imply more
    // content, so a bit of end padding is the only visual cue.
    slidesOffsetAfter: 16,
  });
}

// Trending Now: same wide-card design as New In, but as a 2-row Swiper Grid
// carousel showing 2.5 columns per view. Grid needs a numeric slidesPerView
// (columns) and no fixed !w-[Npx] on the slide — Swiper computes the column
// width itself here, unlike renderWideCard's New In slides.
function mountTrending() {
  const wrapper = document.getElementById("trendingWrapper");
  if (!wrapper) return;
  wrapper.innerHTML = PRODUCTS.trending
    .map((p) => `<div class="swiper-slide">${renderWideCardInner(p)}</div>`)
    .join("");

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 2.5,
    spaceBetween: 16,
    grid: { rows: 2, fill: "row" },
    slidesOffsetAfter: 16,
  });
}

function initCarousels() {
  const categoriesRow = document.getElementById("categoriesRow");
  if (categoriesRow) {
    categoriesRow.innerHTML = CATEGORIES.map(renderCategory).join("");
  }

  mountFlashSale();
  mountCarousel({
    wrapperId: "newInWrapper",
    products: PRODUCTS.newIn,
    renderer: renderWideCard,
  });
  mountTrending();
  renderRecommendedGrid();

  mountBestSellerCarousel(
    "bestSellerMobileWrapper",
    PRODUCTS.bestSellerMobile,
    BEST_SELLER_POSITIONS_MOBILE,
  );
  mountBestSellerCarousel(
    "bestSellerDesktopWrapper",
    PRODUCTS.bestSellerDesktop,
    BEST_SELLER_POSITIONS_DESKTOP,
  );

  // Flash-sale countdown (starts from the design's captured 03:06:25 and ticks down)
  let remaining = 3 * 3600 + 6 * 60 + 25;
  const timerEls = document.querySelectorAll("[data-flash-timer]");
  if (timerEls.length) {
    setInterval(() => {
      remaining = Math.max(0, remaining - 1);
      const h = String(Math.floor(remaining / 3600)).padStart(2, "0");
      const m = String(Math.floor((remaining % 3600) / 60)).padStart(2, "0");
      const s = String(remaining % 60).padStart(2, "0");
      timerEls.forEach((el, i) => {
        el.textContent = [h, m, s][i];
      });
    }, 1000);
  }

  // Mobile feature strip — single rotating feature (matches the design's mobile layout,
  // which shows one feature at a time versus the desktop's full four-item row)
  const featureStripMobile = document.getElementById("featureStripMobile");
  if (featureStripMobile) {
    let featureIndex = 0;
    const renderFeature = (f) => `
      <div class="flex items-center gap-[10px]">
        <img src="${f.icon}" alt="" class="size-[26px] shrink-0" />
        <div class="flex flex-col leading-tight">
          <span class="font-semibold text-[13px] text-white whitespace-nowrap">${f.title}</span>
          <span class="font-normal text-[11px] whitespace-nowrap" style="color:var(--color-stroke)">${f.subtitle}</span>
        </div>
      </div>`;
    featureStripMobile.innerHTML = renderFeature(FEATURES[featureIndex]);
    setInterval(() => {
      featureIndex = (featureIndex + 1) % FEATURES.length;
      featureStripMobile.innerHTML = renderFeature(FEATURES[featureIndex]);
    }, 3000);
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
