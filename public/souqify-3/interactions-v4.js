// Hero carousel + category filter / mobile tab active-state behavior for the Souqify
// home screen (v4). The mobile off-canvas drawer toggle is intentionally NOT handled
// here — it is handled by resources/js/souqify/header-v2.js (shared with v2/v3, id-generic),
// to avoid double-binding the same #mobileMenuBtn / #drawerCloseBtn / #mobileDrawer /
// #mobileDrawerOverlay.

function initHomeUIV4() {
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
    const menu = document.getElementById("souqifyV4DeptMenu");
    if (menu && !menu.classList.contains("hidden") && !e.target.closest("#desktopMenuBtn")) {
      menu.classList.add("hidden");
    }
  });
}

let homeUIV4Initialized = false;
function initHomeUIV4Once() {
  if (homeUIV4Initialized) return;
  homeUIV4Initialized = true;
  initHomeUIV4();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initHomeUIV4Once);
} else {
  initHomeUIV4Once();
}
window.addEventListener("load", initHomeUIV4Once);
