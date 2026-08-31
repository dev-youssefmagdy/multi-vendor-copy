// Hero carousel + mobile off-canvas drawer menu for the ELORA "New User" home screen (Theme #5).

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

  // ---- Categories drawer menu (mobile hamburger + desktop "menu" button) ----
  const menuBtns = [
    document.getElementById("mobileMenuBtn"),
    document.getElementById("desktopMenuBtn"),
  ].filter(Boolean);
  const closeBtn = document.getElementById("drawerCloseBtn");
  const drawer = document.getElementById("mobileDrawer");
  const overlay = document.getElementById("mobileDrawerOverlay");

  function openDrawer() {
    drawer.classList.add("is-open");
    overlay.classList.add("is-open");
    menuBtns.forEach((btn) => btn.setAttribute("aria-expanded", "true"));
    document.body.style.overflow = "hidden";
  }

  function closeDrawer() {
    drawer.classList.remove("is-open");
    overlay.classList.remove("is-open");
    menuBtns.forEach((btn) => btn.setAttribute("aria-expanded", "false"));
    document.body.style.overflow = "";
  }

  if (menuBtns.length && drawer && overlay) {
    menuBtns.forEach((btn) => btn.addEventListener("click", openDrawer));
    closeBtn.addEventListener("click", closeDrawer);
    overlay.addEventListener("click", closeDrawer);
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeDrawer();
    });
  }
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
