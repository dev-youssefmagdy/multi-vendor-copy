// Drawer menu (opens from the left on both mobile and desktop) for the ELORA header (v5).

function initHeaderDrawer() {
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
