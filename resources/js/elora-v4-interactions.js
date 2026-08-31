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
  initFlashSlider();
}

// ---- Flash sale pure-JS slider ----
function initFlashSlider() {
  document.querySelectorAll('[data-flash-slider]').forEach(function (wrapper) {
    var id      = wrapper.getAttribute('data-flash-slider');
    var track   = document.getElementById(id + '-track');
    var dotsEl  = document.getElementById(id + '-dots');
    var prevBtn = wrapper.querySelector('.flash-prev');
    var nextBtn = wrapper.querySelector('.flash-next');

    if (!track) return;

    var isDesktop  = window.matchMedia('(min-width: 1024px)').matches;
    var current    = 0;
    var slides     = Array.from(track.children);
    var total      = slides.length;

    /*
     * Layout model (same axis for both breakpoints):
     *   flex-direction: column, flex-wrap: wrap
     *   Cards stack vertically into columns; the track scrolls LEFT/RIGHT.
     *
     *   Mobile:  rows = 2, cols = ceil(total / 2)
     *   Desktop: rows = 3, cols = ceil(total / 3)
     *
     *   One "page" = one column width + gap.
     *   translateX moves the track left by (page × colWidth).
     */
    function rows()     { return isDesktop ? 3 : 2; }
    function gap()      { return isDesktop ? 12 : 8; }
    function colWidth() { return isDesktop ? 340 : getColWidthMobile(); }
    function getColWidthMobile() {
      // Mobile: 2 columns, 8px gap, full viewport width minus section padding (16px×2)
      var vp = wrapper.offsetWidth || 300;
      return Math.floor((vp - gap()) / 2);
    }
    function totalCols() { return Math.ceil(total / rows()); }

    function buildDots() {
      if (!dotsEl) return;
      dotsEl.innerHTML = '';
      var cols = totalCols();
      if (cols <= 1) return;
      for (var i = 0; i < cols; i++) {
        var dot = document.createElement('button');
        dot.className = 'flash-dot' + (i === 0 ? ' is-active' : '');
        dot.setAttribute('aria-label', 'Column ' + (i + 1));
        dot.setAttribute('data-page', String(i));
        (function (page) {
          dot.addEventListener('click', function () { goTo(page); });
        }(i));
        dotsEl.appendChild(dot);
      }
    }

    function updateDots() {
      if (!dotsEl) return;
      dotsEl.querySelectorAll('.flash-dot').forEach(function (d, i) {
        d.classList.toggle('is-active', i === current);
      });
    }

    function updateArrows() {
      if (prevBtn) prevBtn.disabled = current === 0;
      if (nextBtn) nextBtn.disabled = current >= totalCols() - 1;
    }

    function goTo(page) {
      var cols = totalCols();
      current  = Math.max(0, Math.min(page, cols - 1));
      var step = colWidth() + gap();
      track.style.transform = 'translateX(-' + (current * step) + 'px)';
      updateDots();
      updateArrows();
    }

    if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); });

    // Touch swipe
    var tx0 = 0, ty0 = 0;
    wrapper.addEventListener('touchstart', function (e) {
      tx0 = e.changedTouches[0].clientX;
      ty0 = e.changedTouches[0].clientY;
    }, { passive: true });
    wrapper.addEventListener('touchend', function (e) {
      var dx = e.changedTouches[0].clientX - tx0;
      var dy = e.changedTouches[0].clientY - ty0;
      if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 30) {
        goTo(current + (dx < 0 ? 1 : -1));
      }
    }, { passive: true });

    // Breakpoint change
    window.matchMedia('(min-width: 1024px)').addEventListener('change', function (mq) {
      isDesktop = mq.matches;
      track.style.transform = '';
      current = 0;
      buildDots();
      goTo(0);
    });

    // Init
    buildDots();
    goTo(0);
  });
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
