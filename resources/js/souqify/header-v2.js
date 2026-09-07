// Mobile off-canvas drawer menu for the Souqify header (v2).

function initHeaderDrawer() {
  const menuBtn = document.getElementById("mobileMenuBtn");
  const desktopMenuBtn = document.getElementById("desktopMenuBtn");
  const closeBtn = document.getElementById("drawerCloseBtn");
  const drawer = document.getElementById("mobileDrawer");
  const overlay = document.getElementById("mobileDrawerOverlay");

  function openDrawer() {
    drawer.classList.add("is-open");
    overlay.classList.add("is-open");
    menuBtn?.setAttribute("aria-expanded", "true");
    document.body.style.overflow = "hidden";
  }

  function closeDrawer() {
    drawer.classList.remove("is-open");
    overlay.classList.remove("is-open");
    menuBtn?.setAttribute("aria-expanded", "false");
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

  if (desktopMenuBtn && drawer && overlay) {
    desktopMenuBtn.addEventListener("click", openDrawer);
  }
}

let headerDrawerInitialized = false;
function initHeaderDrawerOnce() {
  if (headerDrawerInitialized) return;
  headerDrawerInitialized = true;
  initHeaderDrawer();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initHeaderDrawerOnce);
} else {
  initHeaderDrawerOnce();
}
window.addEventListener("load", initHeaderDrawerOnce);
