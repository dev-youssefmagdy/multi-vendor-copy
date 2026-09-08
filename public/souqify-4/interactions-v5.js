// Hero carousel + category pill active-state + desktop categories dropdown
// close-on-outside-click for the Souqify home screen (v5). The mobile
// off-canvas drawer toggle is intentionally NOT handled here — it is handled
// by resources/js/souqify/header-v2.js (shared with v2/v3/v4), to avoid
// double-binding the same #mobileMenuBtn / #drawerCloseBtn / #mobileDrawer /
// #mobileDrawerOverlay.

function initHomeUIV5() {
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

  // ---- Mobile category pill row: mark active tab ----
  const pills = document.querySelectorAll(".category-pill");
  pills.forEach((pill) => {
    pill.addEventListener("click", () => {
      pills.forEach((p) => p.classList.remove("is-active"));
      pill.classList.add("is-active");
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

  // ---- Desktop "Shop By Categories" dropdown: close when clicking outside ----
  document.addEventListener("click", (e) => {
    const menu = document.getElementById("souqifyV5DeptMenu");
    if (menu && !menu.classList.contains("hidden") && !e.target.closest("#desktopMenuBtn")) {
      menu.classList.add("hidden");
    }
  });
}

let homeUIV5Initialized = false;
function initHomeUIV5Once() {
  if (homeUIV5Initialized) return;
  homeUIV5Initialized = true;
  initHomeUIV5();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initHomeUIV5Once);
} else {
  initHomeUIV5Once();
}
window.addEventListener("load", initHomeUIV5Once);
