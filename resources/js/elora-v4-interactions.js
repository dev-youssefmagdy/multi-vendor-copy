// Hero carousel + mobile off-canvas drawer menu for the ELORA v4 ("New User" promo) home screen.

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

  // ---- Flash-sale countdown (starts from the design's captured 03:06:25 and ticks down) ----
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
