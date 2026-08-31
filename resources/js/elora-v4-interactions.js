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

  initFlashCountdown();
}

// ---- Flash sale countdown timer ----
function initFlashCountdown() {
  const section = document.querySelector("[data-flash-countdown]");
  if (!section) return;

  const endIso = section.getAttribute("data-flash-countdown");
  if (!endIso) return;

  const endTime = new Date(endIso).getTime();
  if (isNaN(endTime)) return;

  const hhEl = section.querySelector("[data-flash-hh]");
  const mmEl = section.querySelector("[data-flash-mm]");
  const ssEl = section.querySelector("[data-flash-ss]");
  if (!hhEl || !mmEl || !ssEl) return;

  function pad(n) {
    return String(n).padStart(2, "0");
  }

  function tick() {
    const now = Date.now();
    const diff = Math.max(0, endTime - now);

    const totalSecs = Math.floor(diff / 1000);
    const hh = Math.floor(totalSecs / 3600);
    const mm = Math.floor((totalSecs % 3600) / 60);
    const ss = totalSecs % 60;

    hhEl.textContent = pad(hh);
    mmEl.textContent = pad(mm);
    ssEl.textContent = pad(ss);

    if (diff > 0) {
      setTimeout(tick, 1000);
    } else {
      const flashSection = section.closest("section");
      if (flashSection) {
        flashSection.style.transition = "opacity 0.5s";
        flashSection.style.opacity = "0";
        setTimeout(() => {
          flashSection.style.display = "none";
        }, 500);
      }
    }
  }

  tick();
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
