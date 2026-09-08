// Product card rendering + Swiper carousel wiring for the ELORA home screen.
// Card markup mirrors the Figma "Product Card" component (node 2002:1372).

const PRODUCTS = {
  newIn: [
    {
      image: "assets/images/product-hoodie.png",
      badge: "Best Seller",
      badgeBg: "var(--color-primary)",
      name: "Essential Hoodie",
      weight: "200g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: null,
    },
    {
      image: "assets/images/product-sneaker.png",
      badge: "New",
      badgeBg: "var(--color-accent-purple)",
      name: "Essential Shoes",
      weight: "250g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: "20% Off",
    },
    {
      image: "assets/images/flash-pants.png",
      badge: "Sale",
      badgeBg: "var(--color-primary)",
      name: "Classic Pants",
      weight: "300g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$79.00",
      oldPrice: "$99.00",
      discount: "20% Off",
    },
    {
      image: "assets/images/product-hoodie.png",
      badge: "New",
      badgeBg: "var(--color-accent-purple)",
      name: "Everyday Hoodie",
      weight: "210g",
      desc: "Premium cotton blend",
      rating: "4.5 (+620)",
      price: "$95.00",
      oldPrice: null,
      discount: null,
    },
    {
      image: "assets/images/product-sneaker.png",
      badge: "Best Seller",
      badgeBg: "var(--color-primary)",
      name: "Runner Sneakers",
      weight: "260g",
      desc: "Premium cotton blend",
      rating: "4.6 (+1.2k)",
      price: "$110.00",
      oldPrice: "$130.00",
      discount: "15% Off",
    },
    {
      image: "assets/images/flash-pants.png",
      badge: "New",
      badgeBg: "var(--color-accent-purple)",
      name: "Tailored Pants",
      weight: "310g",
      desc: "Premium cotton blend",
      rating: "4.1 (+430)",
      price: "$84.00",
      oldPrice: null,
      discount: null,
    },
    {
      image: "assets/images/product-hoodie.png",
      badge: "Sale",
      badgeBg: "var(--color-primary)",
      name: "Fleece Hoodie",
      weight: "220g",
      desc: "Premium cotton blend",
      rating: "4.3 (+710)",
      price: "$99.00",
      oldPrice: "$120.00",
      discount: "18% Off",
    },
    {
      image: "assets/images/product-sneaker.png",
      badge: "New",
      badgeBg: "var(--color-accent-purple)",
      name: "Trail Sneakers",
      weight: "270g",
      desc: "Premium cotton blend",
      rating: "4.4 (+560)",
      price: "$115.00",
      oldPrice: null,
      discount: null,
    },
  ],
  recommended: [
    {
      image: "assets/images/product-sneaker.png",
      badge: "For You",
      badgeBg: "var(--color-accent-purple)",
      name: "Court Sneakers",
      weight: "255g",
      desc: "Premium cotton blend",
      rating: "4.3 (+390)",
      price: "$92.00",
      oldPrice: null,
      discount: null,
    },
    {
      image: "assets/images/product-hoodie.png",
      badge: "Sale",
      badgeBg: "var(--color-primary)",
      name: "Oversized Hoodie",
      weight: "230g",
      desc: "Premium cotton blend",
      rating: "4.5 (+980)",
      price: "$88.00",
      oldPrice: "$105.00",
      discount: "16% Off",
    },
    {
      image: "assets/images/flash-pants.png",
      badge: "New",
      badgeBg: "var(--color-accent-purple)",
      name: "Relaxed Pants",
      weight: "295g",
      desc: "Premium cotton blend",
      rating: "4.0 (+210)",
      price: "$76.00",
      oldPrice: null,
      discount: null,
    },
    {
      image: "assets/images/product-hoodie.png",
      badge: "For You",
      badgeBg: "var(--color-accent-purple)",
      name: "Zip Hoodie",
      weight: "215g",
      desc: "Premium cotton blend",
      rating: "4.4 (+640)",
      price: "$97.00",
      oldPrice: "$115.00",
      discount: "15% Off",
    },
    {
      image: "assets/images/product-sneaker.png",
      badge: "Sale",
      badgeBg: "var(--color-primary)",
      name: "Street Sneakers",
      weight: "265g",
      desc: "Premium cotton blend",
      rating: "4.2 (+505)",
      price: "$105.00",
      oldPrice: "$125.00",
      discount: "16% Off",
    },
    {
      image: "assets/images/flash-pants.png",
      badge: "New",
      badgeBg: "var(--color-accent-purple)",
      name: "Cargo Pants",
      weight: "320g",
      desc: "Premium cotton blend",
      rating: "4.1 (+330)",
      price: "$82.00",
      oldPrice: null,
      discount: null,
    },
    {
      image: "assets/images/product-hoodie.png",
      badge: "For You",
      badgeBg: "var(--color-accent-purple)",
      name: "Pullover Hoodie",
      weight: "225g",
      desc: "Premium cotton blend",
      rating: "4.6 (+890)",
      price: "$91.00",
      oldPrice: null,
      discount: null,
    },
    {
      image: "assets/images/product-sneaker.png",
      badge: "Sale",
      badgeBg: "var(--color-primary)",
      name: "Classic Sneakers",
      weight: "250g",
      desc: "Premium cotton blend",
      rating: "4.3 (+700)",
      price: "$99.00",
      oldPrice: "$119.00",
      discount: "17% Off",
    },
  ],
  bestSeller: [
    {
      image: "assets/images/product-hoodie.png",
      badge: "Best Seller",
      badgeBg: "var(--color-primary)",
      name: "Essential Hoodie",
      weight: "200g",
      desc: "Premium cotton blend",
      rating: "4.2 (+850)",
      price: "$89.00",
      oldPrice: "$89.00",
      discount: null,
    },
    {
      image: "assets/images/product-sneaker.png",
      badge: "Best Seller",
      badgeBg: "var(--color-primary)",
      name: "Essential Shoes",
      weight: "250g",
      desc: "Premium cotton blend",
      rating: "4.6 (+1.4k)",
      price: "$110.00",
      oldPrice: "$130.00",
      discount: "15% Off",
    },
    {
      image: "assets/images/flash-pants.png",
      badge: "Best Seller",
      badgeBg: "var(--color-primary)",
      name: "Classic Pants",
      weight: "300g",
      desc: "Premium cotton blend",
      rating: "4.4 (+960)",
      price: "$79.00",
      oldPrice: "$99.00",
      discount: "20% Off",
    },
    {
      image: "assets/images/product-hoodie.png",
      badge: "Best Seller",
      badgeBg: "var(--color-primary)",
      name: "Fleece Hoodie",
      weight: "220g",
      desc: "Premium cotton blend",
      rating: "4.3 (+710)",
      price: "$99.00",
      oldPrice: "$120.00",
      discount: "18% Off",
    },
    {
      image: "assets/images/product-sneaker.png",
      badge: "Best Seller",
      badgeBg: "var(--color-primary)",
      name: "Runner Sneakers",
      weight: "260g",
      desc: "Premium cotton blend",
      rating: "4.6 (+1.2k)",
      price: "$110.00",
      oldPrice: null,
      discount: null,
    },
    {
      image: "assets/images/flash-pants.png",
      badge: "Best Seller",
      badgeBg: "var(--color-primary)",
      name: "Tailored Pants",
      weight: "310g",
      desc: "Premium cotton blend",
      rating: "4.1 (+430)",
      price: "$84.00",
      oldPrice: null,
      discount: null,
    },
  ],
};

function renderProductCardInner(p) {
  return `
      <div class="bg-[var(--color-bg-main)] flex flex-col items-start rounded-[8px] h-full">
        <div class="flex flex-col gap-[8px] h-[183px] lg:h-[227px] items-end justify-end px-[6px] py-[5px] relative shrink-0 w-full">
          <div class="absolute flex gap-[8px] h-[183px] lg:h-[227px] items-start left-0 p-[6px] top-0 w-full">
            <div class="absolute flex flex-col gap-[8px] h-[183px] lg:h-[227px] items-end left-0 top-0 w-full">
              <div class="absolute left-1/2 -translate-x-1/2 h-[183px] lg:h-[227px] rounded-t-[8px] top-0 w-full overflow-hidden">
                <img src="${p.image}" alt="${p.name}" class="absolute inset-0 h-full w-full object-cover" />
              </div>
              <div class="content-stretch flex h-[28px] items-center justify-center p-[6px] relative rounded-bl-[8px] rounded-tr-[8px] shrink-0" style="background:${p.badgeBg}">
                <p class="font-medium text-[14px] text-white tracking-[0.5px] whitespace-nowrap">${p.badge}</p>
              </div>
            </div>
            <button type="button" aria-label="Add to favorites" class="bg-white cursor-pointer drop-shadow-[0px_4px_2px_rgba(0,0,0,0.15)] flex items-center justify-center p-[8px] relative rounded-full shrink-0 size-[32px]">
              <img src="assets/icons/heart.svg" alt="" class="size-[20px]" />
            </button>
          </div>
          <div class="bg-[var(--color-bg-main)] flex h-[45px] items-center justify-center px-[12px] py-[4px] relative rounded-[16px] shrink-0 w-[57px]" style="background:var(--color-text-primary)">
            <img src="assets/icons/cart.svg" alt="Add to cart" class="size-[24px]" />
          </div>
        </div>
        <div class="flex flex-col gap-[8px] items-start p-[8px] relative shrink-0 w-full">
          <div class="flex flex-col gap-[4px] items-start tracking-[0.5px] w-full">
            <div class="flex items-center justify-between w-full whitespace-nowrap">
              <p class="font-medium text-[var(--color-text-primary)] text-[16px]">${p.name}</p>
              <p class="font-normal text-[var(--color-primary)] text-[14px] text-center">${p.weight}</p>
            </div>
            <p class="font-normal text-[var(--color-text-subtitle)] text-[14px] w-full">${p.desc}</p>
          </div>
          <div class="flex flex-col gap-[4px] items-start">
            <div class="flex gap-[8px] items-center justify-center">
              <img src="assets/icons/star-rating.svg" alt="" class="h-[10px] w-[69px]" />
              <p class="font-normal text-[var(--color-text-subtitle)] text-[12px] tracking-[0.5px] whitespace-nowrap">${p.rating}</p>
            </div>
            <div class="flex gap-[8px] items-end">
              <p class="font-medium text-[var(--color-text-primary)] text-[18px] whitespace-nowrap">${p.price}</p>
              ${p.oldPrice ? `<p class="font-light text-[var(--color-text-subtitle)] text-[14px] line-through whitespace-nowrap">${p.oldPrice}</p>` : ""}
              ${p.discount ? `<p class="font-normal text-[var(--color-secondary)] text-[12px] tracking-[0.5px] whitespace-nowrap">${p.discount}</p>` : ""}
            </div>
          </div>
          <div class="flex flex-col gap-[4px] items-start w-full">
            <div class="flex gap-[4px] items-center w-full">
              <img src="assets/icons/truck-delivery.svg" alt="" class="size-[18px]" />
              <p class="font-medium text-[var(--color-success)] text-[12px] whitespace-nowrap">Delivered by 24 March</p>
            </div>
          </div>
        </div>
      </div>`;
}

function renderProductCard(p) {
  return `<div class="swiper-slide h-auto !w-[210px] lg:!w-[260px]">${renderProductCardInner(p)}</div>`;
}

// Trending Now: horizontal "mobile card" — image left, details right.
const TRENDING_PRODUCTS = [
  {
    image: "assets/images/product-sneaker.png",
    name: "Essential Shoes",
    weight: "250g",
    badge: "70% Sold",
    badgeBg: "var(--color-accent-yellow)",
    badgeText: "var(--color-black)",
    progress: 83,
    ordered: "5 ordered last 30 min",
    rating: "4.2 (+850)",
    price: "$89.00",
    oldPrice: "$89.00",
    discount: "20% Off",
  },
  {
    image: "assets/images/product-hoodie.png",
    name: "Essential Hoodie",
    weight: "200g",
    badge: "Best Seller",
    badgeBg: "var(--color-primary)",
    badgeText: "var(--color-white)",
    progress: 65,
    ordered: "3 ordered last 30 min",
    rating: "4.2 (+850)",
    price: "$89.00",
    oldPrice: null,
    discount: null,
  },
  {
    image: "assets/images/flash-pants.png",
    name: "Classic Pants",
    weight: "300g",
    badge: "New",
    badgeBg: "var(--color-accent-purple)",
    badgeText: "var(--color-white)",
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
    <div class="swiper-slide !h-[175px] lg:!h-[204px] !w-[300px] lg:!w-[461px]">
      <div class="flex items-center h-full bg-[var(--color-bg-main)] rounded-[10px] shadow-sm overflow-hidden">
        <div class="relative shrink-0 w-[132px] h-[140px] lg:w-[192px] lg:h-[204px]">
          <img src="${p.image}" alt="${p.name}" class="h-full w-full object-cover" />
          <span class="absolute top-0 right-0 text-white text-[12px] font-normal px-[8px] py-[4px] rounded-bl-[8px]" style="background:${p.badgeBg}; color:${p.badgeText}">${p.badge}</span>
          <button type="button" aria-label="Add to favorites" class="absolute top-[6px] left-[6px] bg-white rounded-full p-[5px] shadow">
            <img src="assets/icons/heart.svg" class="size-[16px]" alt="" />
          </button>
          <div class="absolute bottom-[8px] right-[8px] bg-[var(--color-bg-main)] rounded-[8px] p-[6px] shadow">
            <img src="assets/icons/cart.svg" class="size-[20px]" alt="" />
          </div>
        </div>
        <div class="flex-1 p-[12px] flex flex-col gap-[8px]">
          <div class="flex items-center justify-between">
            <p class="font-medium text-[16px]" style="color:var(--color-text-primary)">${p.name}</p>
            <p class="text-[14px]" style="color:var(--color-accent-purple)">${p.weight}</p>
          </div>
          <p class="text-[13px]" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
          <div class="h-[6px] w-full rounded-full" style="background:var(--color-stroke)">
            <div class="h-full rounded-full" style="background:var(--color-accent-purple); width:${p.progress}%"></div>
          </div>
          <p class="text-[11px]" style="color:var(--color-accent-purple)">${p.ordered}</p>
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
    slidesPerView: "auto",
    spaceBetween: 16,
    navigation: { prevEl: "#trendingPrev", nextEl: "#trendingNext" },
  });
}

// Flash Sale: a "messy pile" carousel — the center card sits upright and largest
// (highest z-index), flanked by smaller cards rotated away on either side. The
// 3-position layout cycles across more slides than there are unique products so
// the pile overflows (and stays a real, draggable slider) even on wide screens.
const FLASH_PRODUCTS = [
  {
    image: "assets/images/flash-sneaker.png",
    name: "Essential Shoes",
    weight: "250g",
    price: "$89.00",
    oldPrice: "$89.00",
    discount: "20% Off",
    badge: "70% Sold",
    badgeBg: "var(--color-accent-yellow)",
    badgeText: "var(--color-black)",
    deliveredColor: "var(--color-success)",
  },
  {
    image: "assets/images/flash-hoodie.png",
    name: "Essential Hoodie",
    weight: "200g",
    price: "$89.00",
    oldPrice: "$89.00",
    discount: "20% Off",
    badge: "70% Sold",
    badgeBg: "var(--color-accent-yellow)",
    badgeText: "var(--color-black)",
    deliveredColor: "var(--color-success)",
    desc: "Premium cotton blend",
  },
  {
    image: "assets/images/flash-pants.png",
    name: "Pants",
    weight: "250g",
    price: "$89.00",
    oldPrice: "$89.00",
    discount: "20% Off",
    badge: "30% OFF",
    badgeBg: "var(--color-primary)",
    badgeText: "var(--color-white)",
    deliveredColor: "var(--color-error)",
  },
];

const FLASH_STACK_LAYOUT = [
  { rotate: -4, translateY: 20, scale: 0.86, z: 1 },
  { rotate: 0, translateY: -8, scale: 1, z: 3 },
  { rotate: 4, translateY: 18, scale: 0.85, z: 1 },
];

function renderFlashStackCard(p, layout) {
  return `
    <div class="swiper-slide" style="transform:translateY(${layout.translateY}px) rotate(${layout.rotate}deg) scale(${layout.scale}); z-index:${layout.z};">
      <div class="rounded-[6px] bg-[var(--color-bg-main)] shadow-xl overflow-hidden">
        <div class="relative">
          <img src="${p.image}" alt="${p.name}" class="h-[130px] lg:h-[238px] w-full object-cover" />
          <span class="absolute top-0 left-0 text-[11px] lg:text-[16px] font-normal tracking-[0.3px] px-[7px] py-[5px] rounded-tl-[6px] rounded-br-[9px]" style="background:${p.badgeBg}; color:${p.badgeText}">${p.badge}</span>
          <button type="button" aria-label="Add to favorites" class="absolute top-[6px] right-[6px] bg-white rounded-full p-[6px] shadow">
            <img src="assets/icons/heart.svg" class="size-[14px] lg:size-[20px]" alt="" />
          </button>
        </div>
        <div class="p-[8px] lg:p-[12px] flex flex-col gap-[4px]">
          <div class="flex items-center justify-between">
            <p class="font-medium text-[12px] lg:text-[19px]" style="color:var(--color-text-primary)">${p.name}</p>
            <p class="text-[10px] lg:text-[16px]" style="color:var(--color-primary)">${p.weight}</p>
          </div>
          ${p.desc ? `<p class="text-[10px] lg:text-[15px]" style="color:var(--color-text-subtitle)">${p.desc}</p>` : ""}
          <div class="flex items-end gap-[6px]">
            <p class="font-medium text-[14px] lg:text-[19px]" style="color:var(--color-text-primary)">${p.price}</p>
            <p class="text-[9px] lg:text-[13px] line-through" style="color:var(--color-text-subtitle)">${p.oldPrice}</p>
            <p class="text-[10px] lg:text-[13px]" style="color:var(--color-secondary)">${p.discount}</p>
          </div>
          <p class="text-[9px] lg:text-[13px] font-medium" style="color:${p.deliveredColor}">Delivered by 24 March · Only 5 left</p>
        </div>
      </div>
    </div>`;
}

function mountFlashStack() {
  const wrapper = document.getElementById("flashStackWrapper");
  if (!wrapper) return;
  const total = 9;
  wrapper.innerHTML = Array.from({ length: total }, (_, i) =>
    renderFlashStackCard(
      FLASH_PRODUCTS[i % FLASH_PRODUCTS.length],
      FLASH_STACK_LAYOUT[i % FLASH_STACK_LAYOUT.length],
    ),
  ).join("");

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 3,
    spaceBetween: -20,
  });
}

// Recommended For You is a static grid, not a carousel — each item is a plain grid cell.
function renderProductCardGridItem(p) {
  return `<div class="h-[380px] lg:h-[440px]">${renderProductCardInner(p)}</div>`;
}

// Best Seller: centered/focus carousel — whichever slide is currently active
// (centered) scales up to full size and full opacity; neighbors on either side
// sit scaled down and muted, partially visible. The scale/opacity live on
// .swiper-slide-active vs. the default slide state in styles.css, driven purely
// by Swiper's own active-slide class as it scrolls (not by a fixed per-index
// layout), so the "focused" card is always whichever one is centered.
function renderBestSellerCard(p) {
  return `<div class="swiper-slide best-seller-slide">${renderProductCardInner(p)}</div>`;
}

// Best Seller positions every slide manually (absolute + translateX + scale)
// based purely on its live distance from the active slide, instead of relying
// on Swiper's box-flow layout (flex widths/spaceBetween/centeredSlides). That
// box-flow approach kept fighting the visual scale-down — since CSS transform
// never changes the box Swiper uses for positioning — leaving gaps at the
// outer edges and letting a 6th slide peek in no matter how the spacing was
// tuned. virtualTranslate:true stops Swiper from touching the DOM itself, but
// it still tracks drag/progress internally, so slide.progress stays accurate
// for us to position from directly. This guarantees exactly 5 fully visible,
// evenly overlapping slides, symmetric around whichever one is centered.
const BEST_SELLER_STEP_PX = 145;

function mountBestSeller() {
  const wrapper = document.getElementById("bestSellerWrapper");
  if (!wrapper) return;
  wrapper.innerHTML = PRODUCTS.bestSeller.map(renderBestSellerCard).join("");

  // Swiper measures each slide's natural (in-flow) position/width the first
  // time it runs updateSlides() — during construction, before this "init"
  // handler fires — so switching them to position:absolute only here (not via
  // a static CSS rule) keeps that initial measurement correct while still
  // achieving the fully custom, gap-free final layout. Progress is read for
  // every slide FIRST, in its own pass, before any slide is repositioned —
  // absolutizing one slide mid-loop shifts the still-in-flow slides after it,
  // corrupting their progress reading if done in a single combined pass.
  const applyLayout = (sw) => {
    const progresses = sw.slides.map((slideEl) => slideEl.progress || 0);
    sw.slides.forEach((slideEl, i) => {
      const progress = progresses[i];
      const dist = Math.min(Math.abs(progress), 2);
      const scale = 1 - dist * 0.22;
      const offset = progress * BEST_SELLER_STEP_PX;
      slideEl.style.position = "absolute";
      slideEl.style.top = "50%";
      slideEl.style.left = "50%";
      slideEl.style.transform = `translate(-50%, -50%) translateX(${offset}px) scale(${scale})`;
      slideEl.style.zIndex = String(10 - Math.round(dist * 4));
    });
  };

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    centeredSlides: true,
    initialSlide: 2,
    virtualTranslate: true,
    watchSlidesProgress: true,
    on: {
      init: applyLayout,
      progress: applyLayout,
      setTransition(sw, duration) {
        sw.slides.forEach((slideEl) => {
          slideEl.style.transitionDuration = `${duration}ms`;
        });
      },
    },
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

function initCarousels() {
  mountCarousel({
    wrapperId: "newInWrapper",
    products: PRODUCTS.newIn,
    prevId: "newInPrev",
    nextId: "newInNext",
  });
  mountTrending();
  mountFlashStack();

  const recommendedGrid = document.getElementById("recommendedWrapper");
  if (recommendedGrid) {
    recommendedGrid.innerHTML = PRODUCTS.recommended
      .map(renderProductCardGridItem)
      .join("");
  }

  mountBestSeller();

  // Flash-sale countdown (starts from the design's captured 03:06:25 and ticks down)
  let remaining = 3 * 3600 + 6 * 60 + 25;
  const timerEl = document.getElementById("flashTimer");
  if (timerEl) {
    setInterval(() => {
      remaining = Math.max(0, remaining - 1);
      const h = String(Math.floor(remaining / 3600)).padStart(2, "0");
      const m = String(Math.floor((remaining % 3600) / 60)).padStart(2, "0");
      const s = String(remaining % 60).padStart(2, "0");
      timerEl.textContent = `${h}:${m}:${s}`;
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
