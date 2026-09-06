// Hero carousel + mobile off-canvas drawer menu for the Souqify home screen.

function initHomeUI() {
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

  // ---- Mobile drawer menu ----
  const menuBtn = document.getElementById("mobileMenuBtn");
  const closeBtn = document.getElementById("drawerCloseBtn");
  const drawer = document.getElementById("mobileDrawer");
  const overlay = document.getElementById("mobileDrawerOverlay");

  function openDrawer() {
    drawer.classList.add("is-open");
    overlay.classList.add("is-open");
    menuBtn.setAttribute("aria-expanded", "true");
    document.body.style.overflow = "hidden";
  }

  function closeDrawer() {
    drawer.classList.remove("is-open");
    overlay.classList.remove("is-open");
    menuBtn.setAttribute("aria-expanded", "false");
    document.body.style.overflow = "";
  }

  if (menuBtn && drawer && overlay) {
    menuBtn.addEventListener("click", openDrawer);
    closeBtn.addEventListener("click", closeDrawer);
    overlay.addEventListener("click", closeDrawer);
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeDrawer();
    });
  }

  // ---- Mobile category pill row: mark active tab ----
  const pills = document.querySelectorAll(".category-pill");
  pills.forEach((pill) => {
    pill.addEventListener("click", () => {
      pills.forEach((p) => p.classList.remove("is-active"));
      pill.classList.add("is-active");
    });
  });
}

let homeUIInitialized = false;
function initHomeUIOnce() {
  if (homeUIInitialized) return;
  homeUIInitialized = true;
  initHomeUI();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initHomeUIOnce);
} else {
  initHomeUIOnce();
}
window.addEventListener("load", initHomeUIOnce);
