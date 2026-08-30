// Hero carousel + off-canvas drawer menu + tabbed-products switcher for the
// ELORA "Home v5" screen. Product card markup is rendered server-side in the
// Blade sections (resources/views/themes/elora/pages/home-v5/sections/*.blade.php
// and their partials/ subfolder).

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

  // ---- Drawer menu (opens from the left on both mobile and desktop) ----
  const menuBtn = document.getElementById("mobileMenuBtn");
  const menuBtnDesktop = document.getElementById("mobileMenuBtnDesktop");
  const closeBtn = document.getElementById("drawerCloseBtn");
  const drawer = document.getElementById("mobileDrawer");
  const overlay = document.getElementById("mobileDrawerOverlay");

  function openDrawer() {
    drawer.classList.add("is-open");
    overlay.classList.add("is-open");
    if (menuBtn) menuBtn.setAttribute("aria-expanded", "true");
    document.body.style.overflow = "hidden";
  }

  function closeDrawer() {
    drawer.classList.remove("is-open");
    overlay.classList.remove("is-open");
    if (menuBtn) menuBtn.setAttribute("aria-expanded", "false");
    document.body.style.overflow = "";
  }

  if (drawer && overlay) {
    if (menuBtn) menuBtn.addEventListener("click", openDrawer);
    if (menuBtnDesktop) menuBtnDesktop.addEventListener("click", openDrawer);
    if (closeBtn) closeBtn.addEventListener("click", closeDrawer);
    overlay.addEventListener("click", closeDrawer);
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeDrawer();
    });
  }

  // ---- Tabbed Products pill switcher ----
  document.querySelectorAll("[data-v5-tabs]").forEach((tabsEl) => {
    const buttons = tabsEl.querySelectorAll("[data-v5-tab-target]");
    buttons.forEach((btn) => {
      btn.addEventListener("click", () => {
        const targetId = btn.getAttribute("data-v5-tab-target");
        buttons.forEach((b) => b.classList.remove("is-active"));
        btn.classList.add("is-active");
        document.querySelectorAll(".v5-tab-panel").forEach((panel) => {
          panel.hidden = panel.id !== targetId;
        });
      });
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
