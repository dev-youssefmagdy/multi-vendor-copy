// Product card rendering + Swiper carousel wiring for the ELORA "Theme #4" home screen.
// Card markup mirrors the Figma "Mobile Card" component (e.g. node 2007:8600 / 2007:8904 / 2007:9155).
// All product photos use a single shared placeholder asset per client instruction — only the
// hero, category, and shop-by-category imagery are real exported Figma assets.

const PLACEHOLDER_IMG = "assets/images/product-placeholder.svg";

const FLASH_SALE_PRODUCTS = [
  {
    image: PLACEHOLDER_IMG,
    name: "Ribbed Cami Dress",
    price: "$7.49",
    oldPrice: "$29.99",
    discount: "-75%",
  },
  {
    image: PLACEHOLDER_IMG,
    name: "Oversized Graphic Tee",
    price: "$5.99",
    oldPrice: "$22.99",
    discount: "-74%",
  },
  {
    image: PLACEHOLDER_IMG,
    name: "Wide Leg Linen Pants",
    price: "$9.49",
    oldPrice: "$39.99",
    discount: "-76%",
  },
  {
    image: PLACEHOLDER_IMG,
    name: "Y2K Cargo Skirt",
    price: "$8.99",
    oldPrice: "$34.99",
    discount: "-74%",
  },
];

const PRODUCTS = {
  newIn: [
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Essential Shoes",
      weight: "250g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      urgency: "var(--color-success)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "New In",
      badgeBg: "var(--color-accent-purple)",
      badgeText: "var(--color-white)",
      name: "Everyday Hoodie",
      weight: "210g",
      desc: "Premium cotton blend",
      rating: "4.5 (+620)",
      price: "$95.00",
      oldPrice: null,
      discount: null,
      urgency: "var(--color-success)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Runner Sneakers",
      weight: "260g",
      desc: "Premium cotton blend",
      rating: "4.6 (+1.2k)",
      price: "$110.00",
      oldPrice: "$130.00",
      discount: "15% Off",
      urgency: "var(--color-error)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "New In",
      badgeBg: "var(--color-accent-purple)",
      badgeText: "var(--color-white)",
      name: "Tailored Pants",
      weight: "310g",
      desc: "Premium cotton blend",
      rating: "4.1 (+430)",
      price: "$84.00",
      oldPrice: null,
      discount: null,
      urgency: "var(--color-success)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Fleece Hoodie",
      weight: "220g",
      desc: "Premium cotton blend",
      rating: "4.3 (+710)",
      price: "$99.00",
      oldPrice: "$120.00",
      discount: "18% Off",
      urgency: "var(--color-error)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "New In",
      badgeBg: "var(--color-accent-purple)",
      badgeText: "var(--color-white)",
      name: "Trail Sneakers",
      weight: "270g",
      desc: "Premium cotton blend",
      rating: "4.4 (+560)",
      price: "$115.00",
      oldPrice: null,
      discount: null,
      urgency: "var(--color-success)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Cargo Pants",
      weight: "320g",
      desc: "Premium cotton blend",
      rating: "4.1 (+330)",
      price: "$82.00",
      oldPrice: "$98.00",
      discount: "16% Off",
      urgency: "var(--color-error)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "New In",
      badgeBg: "var(--color-accent-purple)",
      badgeText: "var(--color-white)",
      name: "Zip Hoodie",
      weight: "215g",
      desc: "Premium cotton blend",
      rating: "4.4 (+640)",
      price: "$97.00",
      oldPrice: null,
      discount: null,
      urgency: "var(--color-success)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Street Sneakers",
      weight: "265g",
      desc: "Premium cotton blend",
      rating: "4.2 (+505)",
      price: "$105.00",
      oldPrice: "$125.00",
      discount: "16% Off",
      urgency: "var(--color-error)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Essential Shoes",
      weight: "250g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      urgency: "var(--color-success)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "New In",
      badgeBg: "var(--color-accent-purple)",
      badgeText: "var(--color-white)",
      name: "Everyday Hoodie",
      weight: "210g",
      desc: "Premium cotton blend",
      rating: "4.5 (+620)",
      price: "$95.00",
      oldPrice: null,
      discount: null,
      urgency: "var(--color-success)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Runner Sneakers",
      weight: "260g",
      desc: "Premium cotton blend",
      rating: "4.6 (+1.2k)",
      price: "$110.00",
      oldPrice: "$130.00",
      discount: "15% Off",
      urgency: "var(--color-error)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "New In",
      badgeBg: "var(--color-accent-purple)",
      badgeText: "var(--color-white)",
      name: "Tailored Pants",
      weight: "310g",
      desc: "Premium cotton blend",
      rating: "4.1 (+430)",
      price: "$84.00",
      oldPrice: null,
      discount: null,
      urgency: "var(--color-success)",
    },
  ],
  trending: [
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Essential Hoodie",
      weight: "200g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      progress: 83,
      progressLabel: "5 ordered last 30 min",
      urgency: "var(--color-success)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Essential Hoodie",
      weight: "200g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      progress: 65,
      progressLabel: "4 ordered last 30 min",
      urgency: "var(--color-error)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Essential Hoodie",
      weight: "200g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      progress: 45,
      progressLabel: "3 ordered last 30 min",
      urgency: "var(--color-success)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Essential Hoodie",
      weight: "200g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      progress: 72,
      progressLabel: "5 ordered last 30 min",
      urgency: "var(--color-error)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Essential Hoodie",
      weight: "200g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      progress: 58,
      progressLabel: "3 ordered last 30 min",
      urgency: "var(--color-success)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Essential Hoodie",
      weight: "200g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      progress: 90,
      progressLabel: "6 ordered last 30 min",
      urgency: "var(--color-error)",
    },
  ],
  bestSeller: [
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Essential Hoodie",
      weight: "200g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      progress: 83,
      progressLabel: "5 ordered last 30 min",
      urgency: "var(--color-success)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Essential Hoodie",
      weight: "200g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      progress: 65,
      progressLabel: "4 ordered last 30 min",
      urgency: "var(--color-error)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Essential Hoodie",
      weight: "200g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      progress: 50,
      progressLabel: "3 ordered last 30 min",
      urgency: "var(--color-success)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Essential Hoodie",
      weight: "200g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      progress: 77,
      progressLabel: "5 ordered last 30 min",
      urgency: "var(--color-error)",
    },
  ],
  recommended: [
    {
      image: PLACEHOLDER_IMG,
      badge: "New In",
      badgeBg: "var(--color-accent-purple)",
      badgeText: "var(--color-white)",
      name: "Essential Hoodie",
      weight: "200g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: null,
      discount: null,
      urgency: "var(--color-error)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "30% OFF",
      badgeBg: "var(--color-primary)",
      badgeText: "var(--color-white)",
      name: "Headphone",
      weight: "250g",
      desc: "Premium sound quality",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      urgency: "var(--color-error)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "New In",
      badgeBg: "var(--color-accent-purple)",
      badgeText: "var(--color-white)",
      name: "Essential Hoodie",
      weight: "200g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: null,
      discount: null,
      urgency: "var(--color-success)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Pants",
      weight: "150g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      urgency: "var(--color-success)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "New In",
      badgeBg: "var(--color-accent-purple)",
      badgeText: "var(--color-white)",
      name: "Essential Hoodie",
      weight: "200g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: null,
      discount: null,
      urgency: "var(--color-error)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "New In",
      badgeBg: "var(--color-accent-purple)",
      badgeText: "var(--color-white)",
      name: "Essential Shoes",
      weight: "250g",
      desc: "Premium cotton blend",
      rating: "4.4 (+560)",
      price: "$115.00",
      oldPrice: null,
      discount: null,
      urgency: "var(--color-success)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "30% OFF",
      badgeBg: "var(--color-primary)",
      badgeText: "var(--color-white)",
      name: "Classic Pants",
      weight: "300g",
      desc: "Premium cotton blend",
      rating: "4.1 (+430)",
      price: "$79.00",
      oldPrice: "$99.00",
      discount: "20% Off",
      urgency: "var(--color-error)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Headphone",
      weight: "230g",
      desc: "Premium sound quality",
      rating: "4.5 (+980)",
      price: "$88.00",
      oldPrice: "$105.00",
      discount: "16% Off",
      urgency: "var(--color-success)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "New In",
      badgeBg: "var(--color-accent-purple)",
      badgeText: "var(--color-white)",
      name: "Zip Hoodie",
      weight: "215g",
      desc: "Premium cotton blend",
      rating: "4.4 (+640)",
      price: "$97.00",
      oldPrice: "$115.00",
      discount: "15% Off",
      urgency: "var(--color-error)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "70% Sold",
      badgeBg: "var(--color-accent-yellow)",
      badgeText: "var(--color-text-primary)",
      name: "Street Sneakers",
      weight: "265g",
      desc: "Premium cotton blend",
      rating: "4.2 (+505)",
      price: "$105.00",
      oldPrice: "$125.00",
      discount: "16% Off",
      urgency: "var(--color-success)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "New In",
      badgeBg: "var(--color-accent-purple)",
      badgeText: "var(--color-white)",
      name: "Cargo Pants",
      weight: "320g",
      desc: "Premium cotton blend",
      rating: "4.1 (+330)",
      price: "$82.00",
      oldPrice: null,
      discount: null,
      urgency: "var(--color-error)",
    },
    {
      image: PLACEHOLDER_IMG,
      badge: "30% OFF",
      badgeBg: "var(--color-primary)",
      badgeText: "var(--color-white)",
      name: "Pullover Hoodie",
      weight: "225g",
      desc: "Premium cotton blend",
      rating: "4.6 (+890)",
      price: "$91.00",
      oldPrice: null,
      discount: null,
      urgency: "var(--color-success)",
    },
  ],
};

function renderProductCard(p, { wide } = {}) {
  const imgHeight = wide ? "h-[213px] lg:h-[269px]" : "h-[183px] lg:h-[227px]";
  return `
    <div class="bg-[var(--color-bg-main)] flex flex-col items-start rounded-[8px] h-full shadow-sm">
      <div class="flex flex-col gap-[8px] ${imgHeight} items-end justify-end px-[6px] py-[5px] relative shrink-0 w-full">
        <div class="absolute flex gap-[8px] ${imgHeight} items-start left-0 p-[6px] top-0 w-full">
          <div class="absolute flex flex-col gap-[8px] ${imgHeight} items-end left-0 top-0 w-full">
            <div class="absolute left-1/2 -translate-x-1/2 ${imgHeight} rounded-t-[8px] top-0 w-full overflow-hidden bg-[var(--color-page-bg)]">
              <img src="${p.image}" alt="${p.name}" class="absolute inset-0 h-full w-full object-cover" />
            </div>
            <div class="content-stretch flex h-[28px] items-center justify-center p-[6px] relative rounded-bl-[8px] rounded-tr-[8px] shrink-0" style="background:${p.badgeBg}">
              <p class="font-normal text-[14px] tracking-[0.5px] whitespace-nowrap" style="color:${p.badgeText}">${p.badge}</p>
            </div>
          </div>
          <button type="button" aria-label="Add to favorites" class="bg-white cursor-pointer drop-shadow-[0px_4px_2px_rgba(0,0,0,0.15)] flex items-center justify-center p-[8px] relative rounded-full shrink-0 size-[32px]">
            <img src="assets/icons/heart.svg" alt="" class="size-[20px]" />
          </button>
        </div>
        <div class="flex h-[45px] items-center justify-center px-[12px] py-[4px] relative rounded-[16px] shrink-0 w-[57px]" style="background:var(--color-text-primary)">
          <img src="assets/icons/cart-add.svg" alt="Add to cart" class="size-[24px]" />
        </div>
      </div>
      <div class="flex flex-col gap-[8px] items-start p-[8px] relative shrink-0 w-full">
        <div class="flex flex-col gap-[4px] items-start tracking-[0.5px] w-full">
          <div class="flex items-center justify-between w-full whitespace-nowrap">
            <p class="font-medium text-[16px]" style="color:var(--color-text-primary)">${p.name}</p>
            <p class="font-normal text-[14px] text-center" style="color:var(--color-brand-pink)">${p.weight}</p>
          </div>
          <p class="font-normal text-[14px] w-full" style="color:var(--color-text-subtitle)">${p.desc}</p>
        </div>
        ${
          p.progress != null
            ? `
        <div class="flex flex-col gap-[4px] items-start w-full">
          <div class="h-[6px] w-full rounded-full ordered-progress-track">
            <div class="h-full rounded-full ordered-progress-fill" style="width:${p.progress}%"></div>
          </div>
          <p class="text-[11px] tracking-[0.4px]" style="color:var(--color-progress-fill)">${p.progressLabel}</p>
        </div>`
            : ""
        }
        <div class="flex flex-col gap-[4px] items-start">
          <div class="flex gap-[8px] items-center justify-center">
            <img src="assets/icons/star-rating.svg" alt="" class="h-[10px] w-[69px]" />
            <span class="font-normal text-[12px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-text-subtitle)">${p.rating}</span>
          </div>
          <div class="flex gap-[8px] items-end">
            <p class="font-medium text-[18px] whitespace-nowrap" style="color:var(--color-text-primary)">${p.price}</p>
            ${p.oldPrice ? `<p class="font-light text-[14px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">${p.oldPrice}</p>` : ""}
            ${p.discount ? `<p class="font-normal text-[12px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-secondary)">${p.discount}</p>` : ""}
          </div>
        </div>
        <div class="flex flex-col gap-[4px] items-start w-full">
          <div class="flex gap-[4px] items-center w-full">
            <img src="assets/icons/truck-delivery.svg" alt="" class="size-[18px]" />
            <p class="font-medium text-[12px] whitespace-nowrap" style="color:${p.urgency}">Delivered by 24 March</p>
          </div>
          <div class="flex gap-[4px] items-center w-full">
            <img src="assets/icons/cart-x.svg" alt="" class="size-[16px]" />
            <p class="font-medium text-[12px] whitespace-nowrap" style="color:${p.urgency}">Only 5 left</p>
          </div>
        </div>
      </div>
    </div>`;
}

function renderFlashCard(p) {
  return `
    <div class="swiper-slide h-auto !w-[172px] lg:!w-[272px]">
      <div class="bg-white flex flex-col items-start rounded-[6px] h-full overflow-hidden">
        <div class="relative w-full shrink-0">
          <img src="${p.image}" alt="${p.name}" class="h-[210px] lg:h-[363px] w-full object-cover bg-[var(--color-page-bg)]" />
          <span class="absolute top-[8px] left-[8px] font-extrabold text-[13px] lg:text-[19px] text-white px-[7px] py-[1px] rounded-[3px]" style="background:var(--color-brand-pink)">${p.discount}</span>
          <button type="button" aria-label="Add to favorites" class="absolute top-[8px] right-[8px] bg-white/90 rounded-full p-[8px] lg:p-[9px] shadow"><img src="assets/icons/heart.svg" class="size-[15px] lg:size-[15px]" alt="" /></button>
        </div>
        <div class="p-[10px] lg:p-[11px] flex flex-col gap-[6px] w-full">
          <p class="text-[13px] lg:text-[15px] whitespace-nowrap overflow-hidden text-ellipsis" style="color:var(--color-text-slate)">${p.name}</p>
          <div class="flex items-baseline gap-[8px]">
            <p class="font-extrabold text-[17px] lg:text-[19px]" style="color:var(--color-brand-pink)">${p.price}</p>
            <p class="text-[12px] lg:text-[14px] line-through" style="color:var(--color-text-faint-alt)">${p.oldPrice}</p>
          </div>
        </div>
      </div>
    </div>`;
}

function renderFlashSale() {
  const wrapper = document.getElementById("flashSaleWrapper");
  if (!wrapper) return;
  wrapper.innerHTML = FLASH_SALE_PRODUCTS.map(renderFlashCard).join("");
  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    navigation: {
      prevEl: "#flashSalePrev",
      nextEl: "#flashSaleNext",
    },
  });
}

function mountCarousel({ wrapperId, products, prevId, nextId, wide }) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;
  wrapper.innerHTML = products
    .map(
      (p) =>
        `<div class="swiper-slide h-auto !w-[210px] lg:!w-[260px]">${renderProductCard(p, { wide })}</div>`,
    )
    .join("");

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    navigation: {
      prevEl: `#${prevId}`,
      nextEl: `#${nextId}`,
    },
  });
}

// New In card: horizontal layout (image on the left, details on the right) —
// distinct from renderProductCard's vertical layout used by Trending/Best Seller.
function renderNewInCard(p) {
  return `
    <div class="bg-[var(--color-bg-main)] flex items-stretch rounded-[8px] h-[129px] max-h-[129px] shadow-sm overflow-hidden">
      <div class="relative shrink-0 w-[70px] lg:w-[140px]">
        <img src="${p.image}" alt="${p.name}" class="absolute inset-0 h-full w-full object-cover" />
        <button type="button" aria-label="Add to favorites" class="absolute top-[4px] left-[4px] lg:top-[6px] lg:left-[6px] bg-white cursor-pointer drop-shadow-[0px_4px_2px_rgba(0,0,0,0.15)] flex items-center justify-center p-[3px] lg:p-[5px] rounded-full size-[16px] lg:size-[24px]">
          <img src="assets/icons/heart.svg" alt="" class="size-[9px] lg:size-[14px]" />
        </button>
        <span class="absolute top-0 right-0 text-[8px] lg:text-[11px] font-normal px-[4px] py-[2px] lg:px-[8px] lg:py-[4px] rounded-bl-[6px] lg:rounded-bl-[8px] whitespace-nowrap" style="background:${p.badgeBg}; color:${p.badgeText}">${p.badge}</span>
        <div class="absolute bottom-[4px] right-[4px] lg:bottom-[6px] lg:right-[6px] flex h-[18px] lg:h-[28px] items-center justify-center px-[5px] lg:px-[8px] rounded-[9px] lg:rounded-[12px]" style="background:var(--color-text-primary)">
          <img src="assets/icons/cart-add.svg" alt="Add to cart" class="size-[9px] lg:size-[14px]" />
        </div>
      </div>
      <div class="flex-1 flex flex-col gap-[2px] lg:gap-[3px] p-[6px] lg:p-[10px] min-w-0 justify-center overflow-hidden">
        <div class="flex items-center justify-between gap-[4px] lg:gap-[8px] w-full">
          <p class="font-medium text-[11px] lg:text-[16px] truncate" style="color:var(--color-text-primary)">${p.name}</p>
          <p class="font-normal text-[9px] lg:text-[12px] shrink-0" style="color:var(--color-brand-pink)">${p.weight}</p>
        </div>
        <p class="font-normal text-[9px] lg:text-[12px] truncate" style="color:var(--color-text-subtitle)">${p.desc}</p>
        <div class="flex gap-[4px] lg:gap-[8px] items-center">
          <img src="assets/icons/star-rating.svg" alt="" class="h-[8px] w-[55px] lg:h-[10px] lg:w-[69px]" />
          <span class="font-normal text-[9px] lg:text-[11px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-text-subtitle)">${p.rating}</span>
        </div>
        <div class="flex gap-[4px] lg:gap-[6px] items-end">
          <p class="font-medium text-[11px] lg:text-[16px] whitespace-nowrap" style="color:var(--color-text-primary)">${p.price}</p>
          ${p.oldPrice ? `<p class="font-light text-[9px] lg:text-[12px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">${p.oldPrice}</p>` : ""}
          ${p.discount ? `<p class="font-normal text-[9px] lg:text-[11px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-secondary)">${p.discount}</p>` : ""}
        </div>
        <div class="flex gap-[4px] items-center w-full">
          <img src="assets/icons/truck-delivery.svg" alt="" class="size-[10px] lg:size-[16px] shrink-0" />
          <p class="font-medium text-[9px] lg:text-[12px] truncate" style="color:${p.urgency}">Delivered by 24 March</p>
        </div>
      </div>
    </div>`;
}

// New In: real Swiper carousel using the Grid module — a fixed numeric
// slidesPerView (columns visible) x grid rows:3, so each "slide" is really one
// column and the view shows 3 stacked rows per column. Numeric slidesPerView
// is required for the Grid module's row math to work at all (fractional/auto
// slidesPerView leaves every slide with an identical, wrong row assignment).
// No fixed !w-[] class on the slide — that would fight Swiper's own computed
// column width and silently freeze the visible count regardless of the
// slidesPerView value (learned the hard way on a previous screen).
function mountNewIn() {
  const wrapper = document.getElementById("newInWrapper");
  if (!wrapper) return;
  wrapper.innerHTML = PRODUCTS.newIn
    .map((p) => `<div class="swiper-slide">${renderNewInCard(p)}</div>`)
    .join("");

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 3,
    spaceBetween: 16,
    grid: { rows: 3, fill: "row" },
  });
}

function renderRecommendedGrid() {
  const grid = document.getElementById("recommendedGrid");
  if (!grid) return;
  grid.innerHTML = PRODUCTS.recommended
    .map((p) => `<div>${renderProductCard(p)}</div>`)
    .join("");
}

function initCarousels() {
  renderFlashSale();
  mountNewIn();
  mountCarousel({
    wrapperId: "trendingWrapper",
    products: PRODUCTS.trending,
    prevId: "trendingPrev",
    nextId: "trendingNext",
  });
  mountCarousel({
    wrapperId: "bestSellerWrapper",
    products: PRODUCTS.bestSeller,
    prevId: "bestSellerPrev",
    nextId: "bestSellerNext",
    wide: true,
  });
  renderRecommendedGrid();

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
