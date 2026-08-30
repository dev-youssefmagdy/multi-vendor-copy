// Swiper wiring for the ELORA home screen carousels.
// Product card markup is now rendered server-side in the Blade sections
// (resources/views/themes/elora/pages/home-v2/sections/*.blade.php and their
// partials/ subfolder) instead of being generated here from JS data.

function mountTrending() {
  const wrapper = document.getElementById("trendingWrapper");
  if (!wrapper) return;

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: "auto",
    spaceBetween: 16,
    navigation: { prevEl: "#trendingPrev", nextEl: "#trendingNext" },
  });
}

function mountFlashStack() {
  const wrapper = document.getElementById("flashStackWrapper");
  if (!wrapper) return;

  return new Swiper(wrapper.closest(".swiper"), {
    slidesPerView: 3,
    spaceBetween: -20,
  });
}

// Best Seller: centered/focus carousel — whichever slide is currently active
// (centered) scales up to full size and full opacity; neighbors on either side
// sit scaled down and muted, partially visible. The scale/opacity live on
// .swiper-slide-active vs. the default slide state in styles.css, driven purely
// by Swiper's own active-slide class as it scrolls (not by a fixed per-index
// layout), so the "focused" card is always whichever one is centered.

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

function mountCarousel({ wrapperId, prevId, nextId }) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;

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
    prevId: "newInPrev",
    nextId: "newInNext",
  });
  mountTrending();
  mountFlashStack();
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
