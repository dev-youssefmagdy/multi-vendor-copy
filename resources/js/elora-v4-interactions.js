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
    // Read the real rendered slide width instead of a hardcoded guess (this
    // used to hardcode 340 for desktop while the card's actual CSS width is
    // 470.8px, so dot/arrow paging silently drifted out of alignment with
    // the real column boundaries after the first click).
    function colWidth() {
      var first = slides[0];
      return first ? first.getBoundingClientRect().width : wrapper.offsetWidth / 2;
    }
    function totalCols() { return Math.ceil(total / rows()); }

    function updateArrows() {
      if (prevBtn) prevBtn.disabled = current === 0;
      if (nextBtn) nextBtn.disabled = current >= totalCols() - 1;
    }

    function goTo(page) {
      var cols = totalCols();
      current  = Math.max(0, Math.min(page, cols - 1));
      var step = colWidth() + gap();
      track.style.transform = 'translateX(-' + (current * step) + 'px)';
      updateArrows();
    }

    if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); });

    // Drag / swipe — unified via Pointer Events so a mouse "grab and drag"
    // on desktop and a touch swipe on mobile share one implementation (this
    // used to only wire up touchstart/touchend, so mouse dragging did
    // nothing at all and only the dot bullets could page the carousel).
    var dragging = false;
    var dragPointerId = null;
    var dragStartX = 0;
    var dragStartY = 0;
    var dragStartTranslate = 0;
    var dragAxis = null; // 'x' | 'y' | null (undecided)
    var suppressNextClick = false;

    function currentTranslateX() {
      return -(current * (colWidth() + gap()));
    }

    function onPointerDown(e) {
      if (e.pointerType === 'mouse' && e.button !== 0) return;
      dragging = true;
      dragAxis = null;
      dragPointerId = e.pointerId;
      dragStartX = e.clientX;
      dragStartY = e.clientY;
      dragStartTranslate = currentTranslateX();
      track.style.transition = 'none';
      try { wrapper.setPointerCapture(dragPointerId); } catch (err) {}
    }

    function onPointerMove(e) {
      if (!dragging || e.pointerId !== dragPointerId) return;
      var dx = e.clientX - dragStartX;
      var dy = e.clientY - dragStartY;
      if (dragAxis === null) {
        if (Math.abs(dx) < 5 && Math.abs(dy) < 5) return;
        dragAxis = Math.abs(dx) > Math.abs(dy) ? 'x' : 'y';
        if (dragAxis === 'y') {
          // Vertical intent (page scroll) — bail out, let the page scroll.
          dragging = false;
          track.style.transition = '';
          return;
        }
      }
      e.preventDefault();
      track.style.transform = 'translateX(' + (dragStartTranslate + dx) + 'px)';
    }

    function onPointerUp(e) {
      if (!dragging || e.pointerId !== dragPointerId) return;
      dragging = false;
      track.style.transition = '';
      var dx = e.clientX - dragStartX;
      if (dragAxis === 'x') {
        if (Math.abs(dx) > 5) suppressNextClick = true;
        if (Math.abs(dx) > 40) {
          goTo(current + (dx < 0 ? 1 : -1));
        } else {
          goTo(current); // snap back to the current page
        }
      }
    }

    wrapper.addEventListener('pointerdown', onPointerDown);
    wrapper.addEventListener('pointermove', onPointerMove);
    wrapper.addEventListener('pointerup', onPointerUp);
    wrapper.addEventListener('pointercancel', onPointerUp);
    // Dragging a mouse over an <img>/<a> would otherwise start a native
    // "ghost image" drag instead of moving the track.
    wrapper.addEventListener('dragstart', function (e) { e.preventDefault(); });
    // A real drag shouldn't also fire the product link's click underneath it.
    wrapper.addEventListener('click', function (e) {
      if (suppressNextClick) {
        e.preventDefault();
        e.stopPropagation();
        suppressNextClick = false;
      }
    }, true);

    // Breakpoint change
    window.matchMedia('(min-width: 1024px)').addEventListener('change', function (mq) {
      isDesktop = mq.matches;
      track.style.transform = '';
      current = 0;
      goTo(0);
    });

    // Init
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
    // Capped to 2 digits (% 24) — the timer boxes are sized for exactly two
    // characters and there's no separate "days" box, so an end_date more
    // than a day out must still fit (uncapped this showed values like
    // "2813" for a sale ending in ~117 days).
    const hh = Math.floor(totalSecs / 3600) % 24;
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
