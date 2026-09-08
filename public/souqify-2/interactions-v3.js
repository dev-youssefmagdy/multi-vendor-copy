// Hero carousel + category pill / mobile tab active-state behavior for the Souqify home screen (v3).
// NOTE: the mobile off-canvas drawer toggle is intentionally NOT handled here — it is
// handled by resources/js/souqify/header-v2.js (shared with v2, id-generic), to avoid
// double-binding the same #mobileMenuBtn / #drawerCloseBtn / #mobileDrawer / #mobileDrawerOverlay.

function initHomeUIV3() {
  // ---- Hero carousel ----
  new Swiper(".hero-swiper", {
    loop: true,
    autoplay: {
      delay: 5000,
      disableOnInteraction: false,
    },
    speed: 600,
    pagination: {
      el: ".hero-pagination",
      clickable: true,
      bulletClass: "hero-dot",
      bulletActiveClass: "is-active",
    },
  });

  // ---- Mobile category filter pills (visual "active" state only) ----
  const pills = document.querySelectorAll("[data-category-pill]");
  pills.forEach((pill) => {
    pill.addEventListener("click", () => {
      pills.forEach((p) => p.classList.remove("is-active-pill"));
      pill.classList.add("is-active-pill");
    });
  });

  // ---- Mobile bottom tab bar (visual "active" state only) ----
  const tabs = document.querySelectorAll("[data-mobile-tab]");
  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      tabs.forEach((t) => t.classList.remove("is-active-tab"));
      tab.classList.add("is-active-tab");
    });
  });
}

let homeUIV3Initialized = false;
function initHomeUIV3Once() {
  if (homeUIV3Initialized) return;
  homeUIV3Initialized = true;
  initHomeUIV3();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initHomeUIV3Once);
} else {
  initHomeUIV3Once();
}
window.addEventListener("load", initHomeUIV3Once);
