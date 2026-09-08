// Product card rendering + Swiper carousel wiring for the ELORA "New User" home screen (Theme #5).
// Card markup mirrors the Figma "Mobile Card" component (node 2007:9609).
// Product photography is a shared neutral placeholder (assets/images/product-placeholder.svg) for every
// product-card slot per project direction; all other imagery (hero, categories, tiles) is real exported art.

const PLACEHOLDER_IMG = "assets/images/product-placeholder.svg";

const PRODUCTS = {
  newIn: [
    {
      name: "Essential Hoodie",
      weight: "200g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Graphic Hoodie",
      weight: "210g",
      price: "$95.00",
      oldPrice: null,
      discount: null,
      rating: "4.5 (+620)",
    },
    {
      name: "Court Sneakers",
      weight: "250g",
      price: "$92.00",
      oldPrice: null,
      discount: null,
      rating: "4.3 (+390)",
    },
    {
      name: "Wireless Headphones",
      weight: "180g",
      price: "$79.00",
      oldPrice: "$99.00",
      discount: "20% Off",
      rating: "4.4 (+560)",
    },
    {
      name: "Classic Watch",
      weight: "90g",
      price: "$149.00",
      oldPrice: null,
      discount: null,
      rating: "4.6 (+1.2k)",
    },
    {
      name: "Wireless Mouse",
      weight: "120g",
      price: "$39.00",
      oldPrice: "$49.00",
      discount: "20% Off",
      rating: "4.1 (+430)",
    },
    {
      name: "Oversized Hoodie",
      weight: "230g",
      price: "$88.00",
      oldPrice: "$105.00",
      discount: "16% Off",
      rating: "4.5 (+980)",
    },
    {
      name: "Retro Sneakers",
      weight: "260g",
      price: "$110.00",
      oldPrice: "$130.00",
      discount: "15% Off",
      rating: "4.6 (+1.4k)",
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
    },
    {
      name: "Street Sneakers",
      weight: "265g",
      price: "$105.00",
      oldPrice: "$125.00",
      discount: "16% Off",
      rating: "4.2 (+505)",
    },
    {
      name: "Over-ear Headphones",
      weight: "185g",
      price: "$85.00",
      oldPrice: null,
      discount: null,
      rating: "4.3 (+700)",
    },
    {
      name: "Chrono Watch",
      weight: "95g",
      price: "$159.00",
      oldPrice: "$189.00",
      discount: "16% Off",
      rating: "4.7 (+890)",
    },
    {
      name: "Ergo Mouse",
      weight: "115g",
      price: "$35.00",
      oldPrice: null,
      discount: null,
      rating: "4.0 (+210)",
    },
    {
      name: "Pullover Hoodie",
      weight: "225g",
      price: "$91.00",
      oldPrice: null,
      discount: null,
      rating: "4.6 (+890)",
    },
    {
      name: "Trail Sneakers",
      weight: "270g",
      price: "$115.00",
      oldPrice: null,
      discount: null,
      rating: "4.4 (+560)",
    },
    {
      name: "Fleece Hoodie",
      weight: "220g",
      price: "$99.00",
      oldPrice: "$120.00",
      discount: "18% Off",
      rating: "4.3 (+710)",
    },
    {
      name: "Studio Headphones",
      weight: "190g",
      price: "$99.00",
      oldPrice: "$119.00",
      discount: "17% Off",
      rating: "4.3 (+700)",
    },
    {
      name: "Runner Sneakers",
      weight: "260g",
      price: "$110.00",
      oldPrice: null,
      discount: null,
      rating: "4.6 (+1.2k)",
    },
  ],
  flashSale: [
    {
      name: "Wireless Headphones",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Smartphone Pro",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Wireless Earbuds",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Volt Running Sneakers",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Mechanical Keyboard",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Potted Plant",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Wireless Headphones",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Smartphone Pro",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Wireless Earbuds",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Volt Running Sneakers",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Mechanical Keyboard",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Potted Plant",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
  ],
  bestSeller: [
    {
      name: "Essential Hoodie",
      weight: "200g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Retro Sneakers",
      weight: "200g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Studio Headphones",
      weight: "200g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
    {
      name: "Wireless Mouse",
      weight: "200g",
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
      name: "Retro Sneakers",
      weight: "200g",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
    },
  ],
  trending: [
    {
      name: "Essential Shoes",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      ordered: "5 ordered last 30 min",
      progress: "83%",
    },
    {
      name: "Essential Shoes",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      ordered: "5 ordered last 30 min",
      progress: "83%",
    },
    {
      name: "Essential Shoes",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
      rating: "4.2 (+850)",
      ordered: "5 ordered last 30 min",
      progress: "83%",
    },
  ],
};

function renderProductCardInner(p) {
  return `
      <div class="bg-[var(--color-bg-main)] flex flex-col items-start rounded-[6px] lg:rounded-[8px] h-full shadow-[var(--shadow-card-lg)]">
        <div class="flex flex-col gap-[5px] lg:gap-[8px] h-[115px] lg:h-[183px] items-end justify-end px-[4px] lg:px-[6px] py-[3px] lg:py-[5px] relative shrink-0 w-full">
          <div class="absolute flex gap-[5px] lg:gap-[8px] h-[115px] lg:h-[183px] items-start left-0 p-[4px] lg:p-[6px] top-0 w-full">
            <div class="absolute flex flex-col gap-[5px] lg:gap-[8px] h-[115px] lg:h-[183px] items-end left-0 top-0 w-full">
              <div class="absolute left-1/2 -translate-x-1/2 h-[115px] lg:h-[183px] rounded-t-[5px] lg:rounded-t-[8px] top-0 w-full overflow-hidden">
                <img src="${PLACEHOLDER_IMG}" alt="${p.name}" class="absolute inset-0 h-full w-full object-cover" />
              </div>
              <div class="flex h-[15px] lg:h-[25px] items-center justify-center p-[3px] lg:p-[5px] relative rounded-bl-[4px] lg:rounded-bl-[7px] rounded-tr-[4px] lg:rounded-tr-[7px] shrink-0" style="background:var(--color-accent-yellow)">
                <p class="font-normal text-[8px] lg:text-[13px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-black)">70% Sold</p>
              </div>
            </div>
            <button type="button" aria-label="Add to favorites" class="bg-white cursor-pointer shadow flex items-center justify-center p-[5px] lg:p-[8px] relative rounded-full shrink-0 size-[20px] lg:size-[33px]">
              <img src="assets/icons/heart.svg" alt="" class="size-[13px] lg:size-[21px]" />
            </button>
          </div>
          <div class="flex items-center justify-center px-[8px] lg:px-[12px] py-[3px] lg:py-[4px] relative rounded-[10px] lg:rounded-[17px] shrink-0 h-[28px] lg:h-[47px] w-[36px] lg:w-[59px]" style="background:var(--color-text-primary)">
            <img src="assets/icons/cart.svg" alt="Add to cart" class="size-[15px] lg:size-[25px] invert" />
          </div>
        </div>
        <div class="flex flex-col gap-[4px] lg:gap-[7px] items-start p-[4px] lg:p-[7px] relative shrink-0 w-full">
          <div class="flex flex-col gap-[2px] lg:gap-[4px] items-start w-full">
            <div class="flex items-center justify-between w-full whitespace-nowrap">
              <p class="font-medium text-[12px] lg:text-[20px]" style="color:var(--color-text-primary)">${p.name}</p>
              <p class="font-normal text-[10px] lg:text-[16px]" style="color:var(--color-brand-orange-bright)">${p.weight}</p>
            </div>
            <p class="font-normal text-[10px] lg:text-[16px] w-full" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
          </div>
          <div class="flex flex-col gap-[2px] lg:gap-[4px] items-start">
            <div class="flex gap-[5px] lg:gap-[8px] items-center justify-center">
              <img src="assets/icons/star-rating.svg" alt="" class="h-[6px] lg:h-[10px] w-[43px] lg:w-[71px]" />
              <span class="text-[8px] lg:text-[13px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-text-subtitle)">${p.rating}</span>
            </div>
            <div class="flex gap-[5px] lg:gap-[8px] items-end">
              <p class="font-medium text-[12px] lg:text-[20px] whitespace-nowrap" style="color:var(--color-text-primary)">${p.price}</p>
              ${p.oldPrice ? `<p class="font-light text-[6px] lg:text-[15px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">${p.oldPrice}</p>` : ""}
              ${p.discount ? `<p class="font-normal text-[8px] lg:text-[13px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-secondary)">${p.discount}</p>` : ""}
            </div>
          </div>
          <div class="flex flex-col gap-[2px] lg:gap-[4px] items-start w-full">
            <div class="flex gap-[4px] lg:gap-[7px] items-center w-full">
              <img src="assets/icons/truck-delivery.svg" alt="" class="size-[12px] lg:size-[20px]" />
              <p class="font-medium text-[8px] lg:text-[13px] whitespace-nowrap" style="color:var(--color-success)">Delivered by 24 March</p>
            </div>
          </div>
        </div>
      </div>`;
}

function renderProductCard(p) {
  return `<div class="swiper-slide h-auto !w-[132px] lg:!w-[210px]">${renderProductCardInner(p)}</div>`;
}

// New In composite slide: right-side mini card — horizontal layout (image
// left, details right), no delivery row (kept short so two of these plus the
// 21px gap fit the left vertical card's full height).
function renderNewInSideCard(p) {
  return `
    <div class="bg-[var(--color-bg-main)] flex items-stretch rounded-[6px] lg:rounded-[8px] h-full shadow-[var(--shadow-card-lg)] overflow-hidden">
      <div class="relative shrink-0 w-[36%]">
        <img src="${PLACEHOLDER_IMG}" alt="${p.name}" class="absolute inset-0 h-full w-full object-cover" />
        <button type="button" aria-label="Add to favorites" class="absolute top-[5px] left-[5px] lg:top-[8px] lg:left-[8px] bg-white cursor-pointer shadow flex items-center justify-center p-[4px] lg:p-[6px] rounded-full size-[18px] lg:size-[26px]">
          <img src="assets/icons/heart.svg" alt="" class="size-[10px] lg:size-[15px]" />
        </button>
        <span class="absolute top-0 right-0 text-[8px] lg:text-[12px] font-normal px-[5px] py-[3px] lg:px-[8px] lg:py-[4px] rounded-bl-[5px] lg:rounded-bl-[7px] whitespace-nowrap" style="background:var(--color-accent-yellow); color:var(--color-black)">70% Sold</span>
        <div class="absolute bottom-[5px] right-[5px] lg:bottom-[8px] lg:right-[8px] flex items-center justify-center rounded-[8px] lg:rounded-[12px] h-[20px] lg:h-[32px] px-[5px] lg:px-[9px]" style="background:var(--color-text-primary)">
          <img src="assets/icons/cart.svg" alt="Add to cart" class="size-[11px] lg:size-[17px] invert" />
        </div>
      </div>
      <div class="flex-1 flex flex-col gap-[2px] lg:gap-[6px] p-[6px] lg:p-[12px] min-w-0 justify-center overflow-hidden">
        <div class="flex items-center justify-between gap-[5px] w-full">
          <p class="font-medium text-[12px] lg:text-[20px] truncate" style="color:var(--color-text-primary)">${p.name}</p>
          <p class="font-normal text-[10px] lg:text-[16px] shrink-0" style="color:var(--color-brand-orange-bright)">${p.weight}</p>
        </div>
        <p class="font-normal text-[10px] lg:text-[16px] truncate" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
        <div class="flex gap-[4px] lg:gap-[8px] items-center">
          <img src="assets/icons/star-rating.svg" alt="" class="h-[6px] lg:h-[10px] w-[43px] lg:w-[71px]" />
          <span class="text-[8px] lg:text-[13px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-text-subtitle)">${p.rating}</span>
        </div>
        <div class="flex gap-[5px] lg:gap-[8px] items-end">
          <p class="font-medium text-[13px] lg:text-[20px] whitespace-nowrap" style="color:var(--color-text-primary)">${p.price}</p>
          ${p.oldPrice ? `<p class="font-light text-[8px] lg:text-[15px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">${p.oldPrice}</p>` : ""}
          ${p.discount ? `<p class="font-normal text-[8px] lg:text-[13px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-secondary)">${p.discount}</p>` : ""}
        </div>
      </div>
    </div>`;
}

// New In: composite slide (left = full vertical card, right = two stacked
// horizontal mini-cards with a 21px gap), 1.5 slides per view, no nav buttons.
// A dedicated mount function — kept separate from the shared mountCarousel so
// the Recommended section (which still uses plain renderProductCard slides)
// is unaffected.
function mountNewIn() {
  const wrapper = document.getElementById("newInWrapper");
  if (!wrapper) return;
  const slides = [];
  for (let i = 0; i < PRODUCTS.newIn.length; i += 3) {
    const [left, top, bottom] = PRODUCTS.newIn.slice(i, i + 3);
    if (!left) break;
    slides.push(`
      <div class="swiper-slide h-auto">
        <div class="flex gap-[16px] h-full">
          <div class="shrink-0 w-[240px]">${renderProductCardInner(left)}</div>
          ${
            top || bottom
              ? `<div class="flex-1 min-w-0 flex flex-col gap-[21px]">
            ${top ? `<div class="flex-1 min-h-0">${renderNewInSideCard(top)}</div>` : ""}
            ${bottom ? `<div class="flex-1 min-h-0">${renderNewInSideCard(bottom)}</div>` : ""}
          </div>`
              : ""
          }
        </div>
      </div>`);
  }
  wrapper.innerHTML = slides.join("");

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 1.75,
    spaceBetween: 16,
  });
}

// Trending Now: real Swiper carousel, 2.5 slides per view.
function mountTrending() {
  const wrapper = document.getElementById("trendingWrapper");
  if (!wrapper) return;
  wrapper.innerHTML = PRODUCTS.trending
    .map(
      (p) => `<div class="swiper-slide h-auto">${renderTrendingCard(p)}</div>`,
    )
    .join("");

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 2.5,
    spaceBetween: 16,
  });
}

function renderFlashCard(p) {
  return `
    <div class="swiper-slide h-[148px]">
      <div class="flex gap-[5px] h-[148px] items-start rounded-[10px] shadow-[var(--shadow-card-lg)]" style="background:var(--color-bg-main)">
        <div class="flex flex-col gap-[8px] h-full items-end justify-end px-[6px] py-[5px] relative shrink-0 w-[204px]">
          <div class="absolute flex gap-[8px] h-full items-start left-0 p-[6px] top-0 w-full">
            <div class="absolute flex flex-col gap-[8px] h-full items-end left-0 top-0 w-full">
              <div class="absolute left-1/2 -translate-x-1/2 h-full rounded-t-[8px] top-0 w-full overflow-hidden">
                <img src="${PLACEHOLDER_IMG}" alt="${p.name}" class="absolute inset-0 h-full w-full object-cover" />
              </div>
              <div class="flex h-[25px] items-center justify-center p-[5px] relative rounded-bl-[7px] rounded-tr-[7px] shrink-0" style="background:var(--color-accent-yellow)">
                <p class="font-normal text-[13px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-black)">70% Sold</p>
              </div>
            </div>
            <button type="button" aria-label="Add to favorites" class="bg-white cursor-pointer shadow flex items-center justify-center p-[8px] relative rounded-full shrink-0 size-[33px]">
              <img src="assets/icons/heart.svg" alt="" class="size-[21px]" />
            </button>
          </div>
          <div class="flex items-center justify-center px-[12px] py-[4px] relative rounded-[17px] shrink-0 h-[47px] w-[59px]" style="background:var(--color-text-primary)">
            <img src="assets/icons/cart.svg" alt="Add to cart" class="size-[25px] invert" />
          </div>
        </div>
        <div class="flex flex-1 flex-col gap-[7px] h-full items-start p-[7px] relative min-w-0">
          <div class="flex flex-col gap-[4px] items-start w-full">
            <div class="flex items-center justify-between w-full whitespace-nowrap">
              <p class="font-medium text-[20px]" style="color:var(--color-text-primary)">${p.name}</p>
              <p class="font-normal text-[16px]" style="color:var(--color-brand-orange-bright)">250g</p>
            </div>
            <p class="font-normal text-[16px] w-full" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
          </div>
          <div class="flex flex-col gap-[4px] items-start">
            <div class="flex gap-[8px] items-center justify-center">
              <img src="assets/icons/star-rating.svg" alt="" class="h-[10px] w-[71px]" />
              <span class="text-[13px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-text-subtitle)">${p.rating}</span>
            </div>
            <div class="flex gap-[8px] items-end">
              <p class="font-medium text-[20px] whitespace-nowrap" style="color:var(--color-text-primary)">${p.price}</p>
              <p class="font-light text-[15px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">${p.oldPrice}</p>
              <p class="font-normal text-[13px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-secondary)">${p.discount}</p>
            </div>
          </div>
          <div class="flex flex-col gap-[4px] items-start w-full">
            <div class="flex gap-[7px] items-center w-full">
              <img src="assets/icons/truck-delivery.svg" alt="" class="size-[20px]" />
              <p class="font-medium text-[13px] whitespace-nowrap" style="color:var(--color-success)">Delivered by 24 March</p>
            </div>
            <div class="flex gap-[8px] items-center">
              <img src="assets/icons/cart-x.svg" alt="" class="size-[17px]" />
              <p class="font-medium text-[12px] whitespace-nowrap" style="color:var(--color-success)">Only 5 left</p>
            </div>
          </div>
        </div>
      </div>
    </div>`;
}

function renderBestSellerCard(p) {
  return `
    <div class="swiper-slide h-auto !w-[193px]">
      <div class="flex flex-col items-start rounded-[9px] shadow-[var(--shadow-card-lg)]" style="background:var(--color-bg-main)">
        <div class="flex flex-col gap-[7px] h-[167px] items-end justify-end px-[5px] py-[5px] relative shrink-0 w-full">
          <div class="absolute flex gap-[7px] h-[167px] items-start left-0 p-[5px] top-0 w-full">
            <div class="absolute flex flex-col gap-[7px] h-[167px] items-end left-0 top-0 w-full">
              <div class="absolute left-1/2 -translate-x-1/2 h-[167px] rounded-t-[7px] top-0 w-full overflow-hidden">
                <img src="${PLACEHOLDER_IMG}" alt="${p.name}" class="absolute inset-0 h-full w-full object-cover" />
              </div>
              <div class="flex h-[22px] items-center justify-center p-[5px] relative rounded-bl-[6px] rounded-tr-[6px] shrink-0" style="background:var(--color-accent-yellow)">
                <p class="font-normal text-[11px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-black)">70% Sold</p>
              </div>
            </div>
            <button type="button" aria-label="Add to favorites" class="bg-white cursor-pointer shadow flex items-center justify-center p-[7px] relative rounded-full shrink-0 size-[29px]">
              <img src="assets/icons/heart.svg" alt="" class="size-[18px]" />
            </button>
          </div>
          <div class="flex items-center justify-center px-[11px] py-[4px] relative rounded-[15px] shrink-0 h-[41px] w-[52px]" style="background:var(--color-text-primary)">
            <img src="assets/icons/cart.svg" alt="Add to cart" class="size-[22px] invert" />
          </div>
        </div>
        <div class="flex flex-col gap-[6px] items-start p-[6px] relative shrink-0 w-full">
          <div class="flex flex-col gap-[4px] items-start w-full">
            <div class="flex items-center justify-between w-full whitespace-nowrap">
              <p class="font-medium text-[17px]" style="color:var(--color-text-primary)">${p.name}</p>
              <p class="font-normal text-[15px]" style="color:var(--color-badge-pink)">${p.weight}</p>
            </div>
            <p class="font-normal text-[15px] w-full" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
          </div>
          <div class="flex flex-col gap-[1px] items-start w-full">
            <div class="h-[5px] w-full rounded-full" style="background:var(--color-stroke)">
              <div class="h-full rounded-full" style="background:var(--color-progress-red); width:83%"></div>
            </div>
            <p class="text-[10px] tracking-[0.3px]" style="color:var(--color-progress-red)">5 ordered last 30 min</p>
          </div>
          <div class="flex flex-col gap-[4px] items-start">
            <div class="flex gap-[7px] items-center justify-center">
              <img src="assets/icons/star-rating.svg" alt="" class="h-[9px] w-[63px]" />
              <span class="text-[12px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-text-subtitle)">${p.rating}</span>
            </div>
            <div class="flex gap-[7px] items-end">
              <p class="font-medium text-[17px] whitespace-nowrap" style="color:var(--color-badge-pink)">${p.price}</p>
              <p class="font-light text-[12px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">${p.oldPrice}</p>
              <p class="font-normal text-[12px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-secondary)">${p.discount}</p>
            </div>
          </div>
          <div class="flex flex-col gap-[4px] items-start w-full">
            <div class="flex gap-[6px] items-center w-full">
              <img src="assets/icons/truck-delivery.svg" alt="" class="size-[17px]" />
              <p class="font-medium text-[12px] whitespace-nowrap" style="color:var(--color-success)">Delivered by 24 March</p>
            </div>
          </div>
        </div>
      </div>
    </div>`;
}

function renderTrendingCard(p) {
  return `
    <div class="flex bg-[var(--color-bg-main)] rounded-[10px] shadow-sm h-full overflow-hidden">
      <div class="relative w-[45%] shrink-0">
        <img src="${PLACEHOLDER_IMG}" alt="${p.name}" class="h-full w-full object-cover" />
        <span class="absolute top-0 right-0 text-[12px] lg:text-[16px] font-normal px-[8px] py-[4px] rounded-bl-[8px]" style="background:var(--color-accent-yellow); color:var(--color-black)">70% Sold</span>
        <button type="button" aria-label="Add to favorites" class="absolute top-[6px] left-[6px] bg-white rounded-full p-[5px] shadow"><img src="assets/icons/heart.svg" class="size-[16px] lg:size-[22px]" alt="" /></button>
        <div class="absolute bottom-[8px] right-[8px] rounded-[8px] p-[6px] shadow" style="background:var(--color-text-primary)"><img src="assets/icons/cart.svg" class="size-[20px] lg:size-[26px] invert" alt="" /></div>
      </div>
      <div class="flex-1 p-[12px] lg:p-[14px] flex flex-col gap-[8px]">
        <div class="flex items-center justify-between">
          <p class="font-medium text-[16px] lg:text-[19px]" style="color:var(--color-text-primary)">${p.name}</p>
          <p class="text-[14px] lg:text-[16px]" style="color:var(--color-brand-orange-bright)">250g</p>
        </div>
        <p class="text-[13px] lg:text-[16px]" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
        <div class="h-[6px] lg:h-[8px] w-full rounded-full" style="background:var(--color-stroke)">
          <div class="h-full rounded-full" style="background:var(--color-error); width:${p.progress}"></div>
        </div>
        <p class="text-[11px] lg:text-[13px]" style="color:var(--color-error)">${p.ordered}</p>
        <div class="flex items-center gap-[6px]">
          <img src="assets/icons/star-rating.svg" alt="" class="h-[8px] lg:h-[10px] w-[56px] lg:w-[70px]" />
          <span class="text-[12px] lg:text-[14px]" style="color:var(--color-text-subtitle)">${p.rating}</span>
        </div>
        <div class="flex items-end gap-[6px]">
          <p class="font-medium text-[16px] lg:text-[19px]" style="color:var(--color-text-primary)">${p.price}</p>
          <p class="text-[11px] lg:text-[13px] line-through" style="color:var(--color-text-subtitle)">${p.oldPrice}</p>
          <p class="text-[12px] lg:text-[14px]" style="color:var(--color-secondary)">${p.discount}</p>
        </div>
      </div>
    </div>`;
}

function mountCarousel({
  wrapperId,
  products,
  prevId,
  nextId,
  renderer,
  grid,
}) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;
  wrapper.innerHTML = products.map(renderer || renderProductCard).join("");

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: grid ? 1 : "auto",
    spaceBetween: 16,
    grid: grid ? { rows: grid.rows, fill: "row" } : undefined,
    breakpoints: grid
      ? { 1024: { slidesPerView: grid.slidesPerViewDesktop } }
      : undefined,
    navigation:
      prevId && nextId
        ? { prevEl: `#${prevId}`, nextEl: `#${nextId}` }
        : undefined,
  });
}

function renderRecommendedGrid() {
  const grid = document.getElementById("recommendedWrapper");
  if (!grid) return;
  grid.innerHTML = PRODUCTS.recommended
    .map((p) => `<div class="h-full">${renderProductCardInner(p)}</div>`)
    .join("");
}

function initCarousels() {
  mountNewIn();
  renderRecommendedGrid();
  mountCarousel({
    wrapperId: "flashSaleWrapper",
    products: PRODUCTS.flashSale,
    prevId: "flashSalePrev",
    nextId: "flashSaleNext",
    renderer: renderFlashCard,
    grid: { rows: 3, slidesPerViewDesktop: 2 },
  });
  mountCarousel({
    wrapperId: "bestSellerWrapper",
    products: PRODUCTS.bestSeller,
    prevId: "bestSellerPrev",
    nextId: "bestSellerNext",
    renderer: renderBestSellerCard,
  });

  mountTrending();

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
