// Categories drawer menu (mobile hamburger + desktop "menu" button) for the ELORA header (v3).

function initHeaderDrawer() {
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
