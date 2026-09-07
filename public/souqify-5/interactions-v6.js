// Hero carousel + desktop categories dropdown close-on-outside-click for the
// Souqify home screen (v6). The mobile off-canvas drawer toggle is
// intentionally NOT handled here — it is handled by
// resources/js/souqify/header-v2.js (shared with v2/v3/v4/v5), to avoid
// double-binding the same #mobileMenuBtn / #drawerCloseBtn / #mobileDrawer /
// #mobileDrawerOverlay.

function initHomeUIV6() {
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

  // ---- Desktop "Shop By Categories" dropdown: close when clicking outside ----
  document.addEventListener("click", (e) => {
    const menu = document.getElementById("souqifyV6DeptMenu");
    if (menu && !menu.classList.contains("hidden") && !e.target.closest("#desktopMenuBtn")) {
      menu.classList.add("hidden");
    }
  });
}

let homeUIV6Initialized = false;
function initHomeUIV6Once() {
  if (homeUIV6Initialized) return;
  homeUIV6Initialized = true;
  initHomeUIV6();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initHomeUIV6Once);
} else {
  initHomeUIV6Once();
}
window.addEventListener("load", initHomeUIV6Once);
