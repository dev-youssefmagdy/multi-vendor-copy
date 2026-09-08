// Product card rendering + Swiper carousel wiring for the ELORA "New In" home screen (Screen 2).
// Card markup mirrors the Figma "Mobile Card" product component used across New In / Best Seller / Recommended.

const PRODUCTS = {
  newIn: [
    {
      image: "assets/images/product-placeholder.svg",
      name: "Street Sneakers",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Essential Hoodie",
      weight: "200g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Runner Sneakers",
      weight: "260g",
      price: "$110.00",
      oldPrice: "$130.00",
      discount: "15% Off",
      rating: "4.6 (+1.2k)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Oversized Hoodie",
      weight: "210g",
      price: "$95.00",
      oldPrice: null,
      discount: null,
      rating: "4.5 (+620)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Pullover Hoodie",
      weight: "220g",
      price: "$91.00",
      oldPrice: null,
      discount: null,
      rating: "4.6 (+890)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Court Sneakers",
      weight: "255g",
      price: "$92.00",
      oldPrice: null,
      discount: null,
      rating: "4.3 (+390)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Zip Hoodie",
      weight: "225g",
      price: "$97.00",
      oldPrice: "$115.00",
      discount: "15% Off",
      rating: "4.4 (+640)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Trail Sneakers",
      weight: "270g",
      price: "$115.00",
      oldPrice: null,
      discount: null,
      rating: "4.4 (+560)",
    },
  ],
  bestSeller: [
    {
      image: "assets/images/product-placeholder.svg",
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      badge: "30% OFF",
      badgeBg: "var(--color-primary)",
      badgeColor: "var(--color-white)",
      weightColor: "var(--color-primary)",
      deliveredColor: "var(--color-error)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      badge: "30% OFF",
      badgeBg: "var(--color-primary)",
      badgeColor: "var(--color-white)",
      weightColor: "var(--color-primary)",
      deliveredColor: "var(--color-error)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      badge: "30% OFF",
      badgeBg: "var(--color-primary)",
      badgeColor: "var(--color-white)",
      weightColor: "var(--color-primary)",
      deliveredColor: "var(--color-error)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      badge: "30% OFF",
      badgeBg: "var(--color-primary)",
      badgeColor: "var(--color-white)",
      weightColor: "var(--color-primary)",
      deliveredColor: "var(--color-error)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      badge: "30% OFF",
      badgeBg: "var(--color-primary)",
      badgeColor: "var(--color-white)",
      weightColor: "var(--color-primary)",
      deliveredColor: "var(--color-error)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      badge: "30% OFF",
      badgeBg: "var(--color-primary)",
      badgeColor: "var(--color-white)",
      weightColor: "var(--color-primary)",
      deliveredColor: "var(--color-error)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      badge: "30% OFF",
      badgeBg: "var(--color-primary)",
      badgeColor: "var(--color-white)",
      weightColor: "var(--color-primary)",
      deliveredColor: "var(--color-error)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      badge: "30% OFF",
      badgeBg: "var(--color-primary)",
      badgeColor: "var(--color-white)",
      weightColor: "var(--color-primary)",
      deliveredColor: "var(--color-error)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      badge: "30% OFF",
      badgeBg: "var(--color-primary)",
      badgeColor: "var(--color-white)",
      weightColor: "var(--color-primary)",
      deliveredColor: "var(--color-error)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      badge: "30% OFF",
      badgeBg: "var(--color-primary)",
      badgeColor: "var(--color-white)",
      weightColor: "var(--color-primary)",
      deliveredColor: "var(--color-error)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      badge: "30% OFF",
      badgeBg: "var(--color-primary)",
      badgeColor: "var(--color-white)",
      weightColor: "var(--color-primary)",
      deliveredColor: "var(--color-error)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      badge: "30% OFF",
      badgeBg: "var(--color-primary)",
      badgeColor: "var(--color-white)",
      weightColor: "var(--color-primary)",
      deliveredColor: "var(--color-error)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      badge: "30% OFF",
      badgeBg: "var(--color-primary)",
      badgeColor: "var(--color-white)",
      weightColor: "var(--color-primary)",
      deliveredColor: "var(--color-error)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Weekend Backpack",
      weight: "480g",
      price: "$89.00",
      oldPrice: null,
      discount: null,
      rating: "4.2 (+850)",
      weightColor: "var(--color-primary)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Essential Shoes",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      weightColor: "var(--color-primary)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Essential Shoes",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      weightColor: "var(--color-primary)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: null,
      discount: null,
      rating: "4.2 (+850)",
      badge: "30% OFF",
      badgeBg: "var(--color-primary)",
      badgeColor: "var(--color-white)",
      weightColor: "var(--color-primary)",
      deliveredColor: "var(--color-error)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Weekend Backpack",
      weight: "480g",
      price: "$99.00",
      oldPrice: "$120.00",
      discount: "18% Off",
      rating: "4.3 (+710)",
      weightColor: "var(--color-primary)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Trail Sneakers",
      weight: "270g",
      price: "$115.00",
      oldPrice: null,
      discount: null,
      rating: "4.4 (+560)",
      weightColor: "var(--color-primary)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Smart Watch",
      weight: "60g",
      price: "$129.00",
      oldPrice: "$159.00",
      discount: "19% Off",
      rating: "4.6 (+1.1k)",
      weightColor: "var(--color-primary)",
    },
  ],
  recommended: [
    {
      image: "assets/images/product-placeholder.svg",
      name: "Court Sneakers",
      weight: "255g",
      price: "$92.00",
      oldPrice: null,
      discount: null,
      rating: "4.3 (+390)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Zip Hoodie",
      weight: "225g",
      price: "$97.00",
      oldPrice: "$115.00",
      discount: "15% Off",
      rating: "4.4 (+640)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Weekend Backpack",
      weight: "480g",
      price: "$89.00",
      oldPrice: null,
      discount: null,
      rating: "4.2 (+850)",
      weightColor: "var(--color-primary)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Essential Shoes",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      weightColor: "var(--color-primary)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Classic Tee",
      weight: "180g",
      price: "$45.00",
      oldPrice: "$60.00",
      discount: "25% Off",
      rating: "4.1 (+430)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Smart Watch",
      weight: "60g",
      price: "$129.00",
      oldPrice: "$159.00",
      discount: "19% Off",
      rating: "4.6 (+1.1k)",
      weightColor: "var(--color-primary)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Trail Sneakers",
      weight: "270g",
      price: "$115.00",
      oldPrice: null,
      discount: null,
      rating: "4.4 (+560)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Pullover Hoodie",
      weight: "220g",
      price: "$91.00",
      oldPrice: null,
      discount: null,
      rating: "4.6 (+890)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Pants",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      weightColor: "var(--color-primary)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Street Sneakers",
      weight: "250g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Oversized Hoodie",
      weight: "210g",
      price: "$95.00",
      oldPrice: null,
      discount: null,
      rating: "4.5 (+620)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Trail Backpack",
      weight: "460g",
      price: "$99.00",
      oldPrice: "$120.00",
      discount: "18% Off",
      rating: "4.3 (+710)",
      weightColor: "var(--color-primary)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Runner Sneakers",
      weight: "260g",
      price: "$110.00",
      oldPrice: "$130.00",
      discount: "15% Off",
      rating: "4.6 (+1.2k)",
      weightColor: "var(--color-primary)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Relaxed Tee",
      weight: "190g",
      price: "$42.00",
      oldPrice: null,
      discount: null,
      rating: "4.0 (+210)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Fitness Watch",
      weight: "55g",
      price: "$109.00",
      oldPrice: null,
      discount: null,
      rating: "4.4 (+560)",
      weightColor: "var(--color-primary)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Fleece Hoodie",
      weight: "220g",
      price: "$99.00",
      oldPrice: "$120.00",
      discount: "18% Off",
      rating: "4.3 (+710)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Classic Sneakers",
      weight: "250g",
      price: "$99.00",
      oldPrice: "$119.00",
      discount: "17% Off",
      rating: "4.3 (+700)",
    },
    {
      image: "assets/images/product-placeholder.svg",
      name: "Tailored Pants",
      weight: "310g",
      price: "$84.00",
      oldPrice: null,
      discount: null,
      rating: "4.1 (+430)",
      weightColor: "var(--color-primary)",
    },
  ],
};

function renderProductCardInner(p) {
  const weightColor = p.weightColor || "var(--color-accent-green)";
  const priceColor = p.priceColor || "var(--color-text-primary)";
  const badge = p.badge || "70% Sold";
  const badgeBg = p.badgeBg || "var(--color-accent-yellow)";
  const badgeColor = p.badgeColor || "var(--color-black)";
  const deliveredColor = p.deliveredColor || "var(--color-success)";
  return `
      <div class="bg-[var(--color-bg-main)] flex flex-col items-start rounded-[6px] h-full shadow-[var(--shadow-card-lg)]">
        <div class="flex flex-col gap-[8px] h-[183px] lg:h-[227px] items-end justify-end px-[6px] py-[5px] relative shrink-0 w-full">
          <div class="absolute flex gap-[8px] h-[183px] lg:h-[227px] items-start left-0 p-[6px] top-0 w-full">
            <div class="absolute flex flex-col gap-[8px] h-[183px] lg:h-[227px] items-end left-0 top-0 w-full">
              <div class="absolute left-1/2 -translate-x-1/2 h-[183px] lg:h-[227px] rounded-t-[6px] top-0 w-full overflow-hidden">
                <img src="${p.image}" alt="${p.name}" class="absolute inset-0 h-full w-full object-cover" />
              </div>
              <div class="content-stretch flex h-[28px] items-center justify-center p-[6px] relative rounded-bl-[8px] rounded-tr-[8px] shrink-0" style="background:${badgeBg}">
                <p class="font-medium text-[14px] tracking-[0.5px] whitespace-nowrap" style="color:${badgeColor}">${badge}</p>
              </div>
            </div>
            <button type="button" aria-label="Add to favorites" class="bg-white cursor-pointer drop-shadow-[0px_4px_2px_rgba(0,0,0,0.15)] flex items-center justify-center p-[8px] relative rounded-full shrink-0 size-[32px]">
              <img src="assets/icons/heart.svg" alt="" class="size-[20px]" />
            </button>
          </div>
          <div class="bg-[var(--color-bg-main)] flex h-[45px] items-center justify-center px-[12px] py-[4px] relative rounded-[16px] shrink-0 w-[57px]">
            <img src="assets/icons/cart.svg" alt="Add to cart" class="size-[24px]" />
          </div>
        </div>
        <div class="flex flex-col gap-[8px] items-start p-[8px] relative shrink-0 w-full">
          <div class="flex flex-col gap-[4px] items-start tracking-[0.5px] w-full">
            <div class="flex items-center justify-between w-full whitespace-nowrap">
              <p class="font-medium text-[var(--color-text-primary)] text-[16px]">${p.name}</p>
              <p class="font-normal text-[14px] text-center" style="color:${weightColor}">${p.weight}</p>
            </div>
            <p class="font-normal text-[var(--color-text-subtitle)] text-[14px] w-full">Premium cotton blend</p>
          </div>
          <div class="flex flex-col gap-[4px] items-start">
            <div class="flex gap-[8px] items-center justify-center">
              <img src="assets/icons/star-rating.svg" alt="" class="h-[10px] w-[69px]" />
              <p class="font-normal text-[var(--color-text-subtitle)] text-[12px] tracking-[0.5px] whitespace-nowrap">${p.rating}</p>
            </div>
            <div class="flex gap-[8px] items-end">
              <p class="font-medium text-[18px] whitespace-nowrap" style="color:${priceColor}">${p.price}</p>
              ${p.oldPrice ? `<p class="font-light text-[var(--color-text-subtitle)] text-[14px] line-through whitespace-nowrap">${p.oldPrice}</p>` : ""}
              ${p.discount ? `<p class="font-normal text-[var(--color-secondary)] text-[12px] tracking-[0.5px] whitespace-nowrap">${p.discount}</p>` : ""}
            </div>
          </div>
          <div class="flex flex-col gap-[4px] items-start w-full">
            <div class="flex gap-[4px] items-center w-full">
              <img src="assets/icons/truck-delivery.svg" alt="" class="size-[18px]" />
              <p class="font-medium text-[12px] whitespace-nowrap" style="color:${deliveredColor}">Delivered by 24 March</p>
            </div>
            <div class="flex gap-[6px] items-center w-full">
              <img src="assets/icons/cart-x.svg" alt="" class="size-[14px]" />
              <p class="font-medium text-[12px] whitespace-nowrap" style="color:${deliveredColor}">Only 5 left</p>
            </div>
          </div>
        </div>
      </div>`;
}

function renderProductCard(p) {
  return `<div class="swiper-slide h-auto !w-[210px] lg:!w-[260px]">${renderProductCardInner(p)}</div>`;
}

function mountCarousel({ wrapperId, products, prevId, nextId }) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;
  wrapper.innerHTML = products.map(renderProductCard).join("");

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    ...(prevId && nextId
      ? { navigation: { prevEl: `#${prevId}`, nextEl: `#${nextId}` } }
      : {}),
  });
}

// Trending Now: horizontal "mobile card" carousel, 2.5 slides per view (no nav
// buttons), so the next card's edge always peeks in as a scroll affordance.
const TRENDING_PRODUCTS = [
  {
    name: "Essential Shoes",
    weight: "250g",
    progress: 83,
    ordered: "5 ordered last 30 min",
    rating: "4.2 (+850)",
    price: "$89.00",
    oldPrice: "$89.00",
    discount: "20% Off",
  },
  {
    name: "Essential Shoes",
    weight: "250g",
    progress: 65,
    ordered: "3 ordered last 30 min",
    rating: "4.2 (+850)",
    price: "$89.00",
    oldPrice: null,
    discount: null,
  },
  {
    name: "Essential Shoes",
    weight: "250g",
    progress: 45,
    ordered: "2 ordered last 30 min",
    rating: "4.1 (+430)",
    price: "$79.00",
    oldPrice: "$99.00",
    discount: "20% Off",
  },
];

function renderTrendingCard(p) {
  return `
    <div class="swiper-slide h-auto !w-[280px] lg:!w-[420px]">
      <div class="flex h-full bg-[var(--color-bg-main)] rounded-[10px] shadow-sm overflow-hidden">
        <div class="relative w-[45%] shrink-0">
          <img src="assets/images/product-placeholder.svg" alt="${p.name}" class="h-full w-full object-cover" />
          <span class="absolute top-0 right-0 text-[12px] font-normal px-[8px] py-[4px] rounded-bl-[8px]" style="background:var(--color-accent-yellow); color:var(--color-black)">70% Sold</span>
          <button type="button" aria-label="Add to favorites" class="absolute top-[6px] left-[6px] bg-white rounded-full p-[5px] shadow"><img src="assets/icons/heart.svg" class="size-[16px]" alt="" /></button>
          <div class="absolute bottom-[8px] right-[8px] bg-[var(--color-bg-main)] rounded-[8px] p-[6px] shadow"><img src="assets/icons/cart.svg" class="size-[20px]" alt="" /></div>
        </div>
        <div class="flex-1 p-[12px] flex flex-col gap-[8px]">
          <div class="flex items-center justify-between">
            <p class="font-medium text-[16px]" style="color:var(--color-text-primary)">${p.name}</p>
            <p class="text-[14px]" style="color:var(--color-accent-green)">${p.weight}</p>
          </div>
          <p class="text-[13px]" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
          <div class="h-[6px] w-full rounded-full" style="background:var(--color-progress-track)">
            <div class="h-full rounded-full" style="background:var(--color-accent-green); width:${p.progress}%"></div>
          </div>
          <p class="text-[11px]" style="color:var(--color-accent-green)">${p.ordered}</p>
          <div class="flex items-center gap-[6px]">
            <img src="assets/icons/star-rating.svg" alt="" class="h-[8px] w-[56px]" />
            <span class="text-[12px]" style="color:var(--color-text-subtitle)">${p.rating}</span>
          </div>
          <div class="flex items-end gap-[6px]">
            <p class="font-medium text-[16px]" style="color:var(--color-text-primary)">${p.price}</p>
            ${p.oldPrice ? `<p class="text-[11px] line-through" style="color:var(--color-text-subtitle)">${p.oldPrice}</p>` : ""}
            ${p.discount ? `<p class="text-[12px]" style="color:var(--color-secondary)">${p.discount}</p>` : ""}
          </div>
        </div>
      </div>
    </div>`;
}

function mountTrending() {
  const wrapper = document.getElementById("trendingWrapper");
  if (!wrapper) return;
  wrapper.innerHTML = TRENDING_PRODUCTS.map(renderTrendingCard).join("");

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 2.5,
    spaceBetween: 16,
    slidesOffsetAfter: 16,
  });
}

// Flash Sale: mini horizontal cards arranged in a fixed 3-row grid that scrolls
// horizontally (grid-auto-flow:column), not a Swiper — matches the same 3-row
// carousel technique used elsewhere in this project (Swiper's own Grid module
// produces uneven row heights on non-exact item/column divisions). No nav
// buttons; the row just scrolls natively.
const FLASH_PRODUCTS = Array.from({ length: 18 }, () => ({
  name: "Essential Shoes",
  price: "$89.00",
  oldPrice: "$89.00",
  discount: "20% Off",
}));

function renderFlashCard(p) {
  return `
    <div class="swiper-slide">
      <div class="flex gap-[5px] rounded-[10px] overflow-hidden h-full" style="background:var(--color-bg-main)">
        <div class="relative w-[45%] max-h-[148px] shrink-0">
          <img src="assets/images/product-placeholder.svg" alt="${p.name}" class="h-full max-h-[148px] w-full object-cover rounded-l-[8px]" />
          <span class="absolute top-0 left-0 text-[12px] font-normal px-[6px] py-[4px] rounded-bl-[8px] rounded-tr-[8px]" style="background:var(--color-accent-yellow); color:var(--color-black)">70% Sold</span>
        </div>
        <div class="flex-1 p-[8px] flex flex-col gap-[4px] min-w-0">
          <div class="flex items-center justify-between">
            <p class="font-medium text-[16px] truncate" style="color:var(--color-text-primary)">${p.name}</p>
            <p class="text-[14px] shrink-0" style="color:var(--color-accent-green)">250g</p>
          </div>
          <p class="text-[13px]" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
          <div class="flex items-center gap-[6px]">
            <img src="assets/icons/star-rating.svg" alt="" class="h-[8px] w-[56px]" />
            <span class="text-[12px]" style="color:var(--color-text-subtitle)">4.2 (+850)</span>
          </div>
          <div class="flex items-end gap-[6px]">
            <p class="font-medium text-[16px]" style="color:var(--color-text-primary)">${p.price}</p>
            <p class="text-[11px] line-through" style="color:var(--color-text-subtitle)">${p.oldPrice}</p>
            <p class="text-[12px]" style="color:var(--color-secondary)">${p.discount}</p>
          </div>
          <p class="text-[11px] font-medium" style="color:var(--color-success)">Delivered by 24 March · Only 5 left</p>
        </div>
      </div>
    </div>`;
}

// Flash Sale: real Swiper carousel using the Grid module — slidesPerView:3
// (3 columns visible) x grid rows:3 = 9 products per view/page. 18 items is
// an exact multiple of 9, so it pages cleanly across exactly 2 full views
// with no partial/uneven row. No nav buttons; swipe/drag pages between views.
function mountFlashGrid() {
  const grid = document.getElementById("flashGrid");
  if (!grid) return;
  grid.innerHTML = FLASH_PRODUCTS.map(renderFlashCard).join("");

  return new Swiper(grid.closest(".swiper"), {
    slidesPerView: 3,
    spaceBetween: 12,
    grid: { rows: 3, fill: "row" },
  });
}

function renderGridCard(p) {
  const weightColor = p.weightColor || "var(--color-accent-green)";
  const priceColor = p.priceColor || "var(--color-text-primary)";
  return `
    <div class="bg-[var(--color-bg-main)] flex flex-col items-start rounded-[6px] shadow-[var(--shadow-card)] overflow-hidden">
      <div class="flex flex-col gap-[8px] h-[160px] lg:h-[227px] items-end justify-end px-[6px] py-[5px] relative shrink-0 w-full">
        <div class="absolute flex gap-[8px] h-[160px] lg:h-[227px] items-start left-0 p-[6px] top-0 w-full">
          <div class="absolute flex flex-col gap-[8px] h-[160px] lg:h-[227px] items-end left-0 top-0 w-full">
            <div class="absolute left-1/2 -translate-x-1/2 h-[160px] lg:h-[227px] rounded-t-[6px] top-0 w-full overflow-hidden">
              <img src="${p.image}" alt="${p.name}" class="absolute inset-0 h-full w-full object-cover" />
            </div>
          </div>
          <button type="button" aria-label="Add to favorites" class="bg-white cursor-pointer drop-shadow-[0px_4px_2px_rgba(0,0,0,0.15)] flex items-center justify-center p-[8px] relative rounded-full shrink-0 size-[32px] ml-auto">
            <img src="assets/icons/heart.svg" alt="" class="size-[20px]" />
          </button>
        </div>
        <div class="bg-[var(--color-bg-main)] flex h-[40px] items-center justify-center px-[10px] py-[4px] relative rounded-[16px] shrink-0 w-[50px]">
          <img src="assets/icons/cart.svg" alt="Add to cart" class="size-[20px]" />
        </div>
      </div>
      <div class="flex flex-col gap-[6px] items-start p-[8px] relative shrink-0 w-full">
        <div class="flex flex-col gap-[4px] items-start tracking-[0.5px] w-full">
          <div class="flex items-center justify-between w-full whitespace-nowrap">
            <p class="font-medium text-[var(--color-text-primary)] text-[14px] lg:text-[16px]">${p.name}</p>
            <p class="font-normal text-[12px] lg:text-[14px] text-center" style="color:${weightColor}">${p.weight}</p>
          </div>
        </div>
        <div class="flex gap-[6px] items-center">
          <img src="assets/icons/star-rating.svg" alt="" class="h-[9px] w-[62px]" />
          <p class="font-normal text-[var(--color-text-subtitle)] text-[11px] tracking-[0.5px] whitespace-nowrap">${p.rating}</p>
        </div>
        <div class="flex gap-[6px] items-end">
          <p class="font-medium text-[16px] whitespace-nowrap" style="color:${priceColor}">${p.price}</p>
          ${p.oldPrice ? `<p class="font-light text-[var(--color-text-subtitle)] text-[12px] line-through whitespace-nowrap">${p.oldPrice}</p>` : ""}
          ${p.discount ? `<p class="font-normal text-[var(--color-secondary)] text-[11px] tracking-[0.5px] whitespace-nowrap">${p.discount}</p>` : ""}
        </div>
      </div>
    </div>`;
}

function mountGrid(gridId, products) {
  const grid = document.getElementById(gridId);
  if (!grid) return;
  grid.innerHTML = products.map(renderGridCard).join("");
}

// Best Seller: each carousel slide is a composite of 4 products — one full
// card on the left, and a right-hand column split into 2 small cards on top
// plus 1 wide horizontal card on the bottom (matching the Figma layout).
function renderBestSellerSmallCard(p) {
  const badge = p.badge || "70% Sold";
  const badgeBg = p.badgeBg || "var(--color-accent-yellow)";
  const badgeColor = p.badgeColor || "var(--color-black)";
  const deliveredColor = "var(--color-success)";
  return `
    <div class="flex-1 bg-[var(--color-bg-main)] flex flex-col items-start rounded-[6px] overflow-hidden shadow-[var(--shadow-card)]">
      <div class="relative h-[120px] lg:h-[166px] w-full shrink-0">
        <img src="${p.image}" alt="${p.name}" class="absolute inset-0 h-full w-full object-cover" />
        <span class="absolute top-0 right-0 text-[11px] font-medium px-[6px] py-[3px] rounded-bl-[8px]" style="background:${badgeBg}; color:${badgeColor}">${badge}</span>
        <button type="button" aria-label="Add to favorites" class="absolute top-[6px] left-[6px] bg-white rounded-full p-[5px] shadow"><img src="assets/icons/heart.svg" class="size-[14px]" alt="" /></button>
        <div class="absolute bottom-[6px] right-[6px] bg-white rounded-[8px] p-[5px] shadow"><img src="assets/icons/cart.svg" class="size-[16px]" alt="" /></div>
      </div>
      <div class="flex flex-col gap-[4px] items-start p-[8px] w-full min-w-0">
        <div class="flex items-center justify-between w-full">
          <p class="font-medium text-[14px] truncate" style="color:var(--color-text-primary)">${p.name}</p>
          <p class="text-[12px] shrink-0" style="color:${p.weightColor || "var(--color-accent-green)"}">${p.weight}</p>
        </div>
        <div class="flex items-center gap-[4px]">
          <img src="assets/icons/star-rating.svg" alt="" class="h-[7px] w-[48px]" />
          <span class="text-[10px]" style="color:var(--color-text-subtitle)">${p.rating}</span>
        </div>
        <div class="flex items-end gap-[5px]">
          <p class="font-medium text-[14px]" style="color:${p.priceColor || "var(--color-text-primary)"}">${p.price}</p>
          ${p.oldPrice ? `<p class="text-[10px] line-through" style="color:var(--color-text-subtitle)">${p.oldPrice}</p>` : ""}
        </div>
            <div class="flex gap-[4px] items-center w-full">
              <img src="assets/icons/truck-delivery.svg" alt="" class="size-[16px]" />
              <p class="font-medium text-[10px] whitespace-nowrap" style="color:${deliveredColor}">Delivered by 24 March</p>
            </div>
      </div>
    </div>`;
}

function renderBestSellerWideCard(p) {
  return `
    <div class="flex-1 flex bg-[var(--color-bg-main)] rounded-[6px] overflow-hidden shadow-[var(--shadow-card)]">
      <div class="relative w-[38%] shrink-0">
        <img src="${p.image}" alt="${p.name}" class="h-full w-full object-cover" />
      </div>
      <div class="flex-1 p-[8px] flex flex-col justify-center gap-[4px] min-w-0">
        <p class="font-medium text-[14px] truncate" style="color:var(--color-text-primary)">${p.name}</p>
        <div class="flex items-center gap-[4px]">
          <img src="assets/icons/star-rating.svg" alt="" class="h-[7px] w-[48px]" />
          <span class="text-[10px]" style="color:var(--color-text-subtitle)">${p.rating}</span>
        </div>
        <div class="flex items-end gap-[5px]">
          <p class="font-medium text-[14px]" style="color:${p.priceColor || "var(--color-primary)"}">${p.price}</p>
          ${p.oldPrice ? `<p class="text-[10px] line-through" style="color:var(--color-text-subtitle)">${p.oldPrice}</p>` : ""}
        </div>
      </div>
    </div>`;
}

function renderBestSellerSlide(group) {
  const [full, small1, small2, wide] = group;
  return `
    <div class="swiper-slide h-auto">
      <div class="flex gap-[12px] h-full">
        <div class="flex-1">${renderProductCardInner(full)}</div>
        <div class="flex-1 flex flex-col gap-[12px]">
          <div class="flex gap-[12px] flex-1">
            ${renderBestSellerSmallCard(small1)}
            ${renderBestSellerSmallCard(small2)}
          </div>
          ${renderBestSellerWideCard(wide)}
        </div>
      </div>
    </div>`;
}

function mountBestSeller() {
  const wrapper = document.getElementById("bestSellerWrapper");
  if (!wrapper) return;
  const groups = [];
  for (let i = 0; i < PRODUCTS.bestSeller.length; i += 4) {
    groups.push(PRODUCTS.bestSeller.slice(i, i + 4));
  }
  wrapper.innerHTML = groups.map(renderBestSellerSlide).join("");

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 1.75,
    spaceBetween: 16,
    navigation: { prevEl: "#bestSellerPrev", nextEl: "#bestSellerNext" },
  });
}

function initCarousels() {
  mountCarousel({ wrapperId: "newInWrapper", products: PRODUCTS.newIn });
  mountBestSeller();
  mountGrid("recommendedGrid", PRODUCTS.recommended);
  mountTrending();
  mountFlashGrid();

  // Flash-sale countdown (starts from the design's captured 03:06:25 and ticks down)
  let remaining = 3 * 3600 + 6 * 60 + 25;
  const timerEl = document.getElementById("flashTimerH");
  const timerElM = document.getElementById("flashTimerM");
  const timerElS = document.getElementById("flashTimerS");
  if (timerEl && timerElM && timerElS) {
    setInterval(() => {
      remaining = Math.max(0, remaining - 1);
      const h = String(Math.floor(remaining / 3600)).padStart(2, "0");
      const m = String(Math.floor((remaining % 3600) / 60)).padStart(2, "0");
      const s = String(remaining % 60).padStart(2, "0");
      timerEl.textContent = h;
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
