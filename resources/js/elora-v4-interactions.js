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
    var id     = wrapper.getAttribute('data-flash-slider');
    var track  = document.getElementById(id + '-track');
    var dotsEl = document.getElementById(id + '-dots');
    var prevBtn = wrapper.querySelector('.flash-prev');
    var nextBtn = wrapper.querySelector('.flash-next');

    if (!track) return;

    var isDesktop = window.matchMedia('(min-width: 1024px)').matches;
    var current   = 0;
    var slides    = Array.from(track.children);
    var total     = slides.length;
    var perPage   = isDesktop ? 2 : 4; // desktop: 2 cards wide; mobile: 4 cards (2×2)
    var pages     = Math.ceil(total / perPage);

    // Build dots
    if (dotsEl && pages > 1) {
      dotsEl.innerHTML = '';
      for (var i = 0; i < pages; i++) {
        var dot = document.createElement('button');
        dot.className = 'flash-dot' + (i === 0 ? ' is-active' : '');
        dot.setAttribute('aria-label', 'Page ' + (i + 1));
        dot.setAttribute('data-page', i);
        dot.addEventListener('click', function () {
          goTo(parseInt(this.getAttribute('data-page'), 10));
        });
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
      if (nextBtn) nextBtn.disabled = current >= pages - 1;
    }

    function goTo(page) {
      current = Math.max(0, Math.min(page, pages - 1));

      if (isDesktop) {
        // Horizontal scroll: each page moves by (perPage × (slideWidth + gap))
        var slideWidth = slides[0] ? slides[0].offsetWidth : 340;
        var gap = 12;
        var offset = current * perPage * (slideWidth + gap);
        track.style.transform = 'translateX(-' + offset + 'px)';
      } else {
        // Mobile: 2-column wrap, scroll vertically by page
        // Each page = 2 rows × (148px card + 8px gap) = 312px per page
        var rowH  = 148 + 8;
        var rows  = 2; // 4 cards per page = 2 rows of 2
        var vOffset = current * rows * rowH;
        track.style.transform = 'translateY(-' + vOffset + 'px)';
      }

      updateDots();
      updateArrows();
    }

    if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); });

    // Touch swipe
    var touchStartX = 0, touchStartY = 0;
    wrapper.addEventListener('touchstart', function (e) {
      touchStartX = e.changedTouches[0].clientX;
      touchStartY = e.changedTouches[0].clientY;
    }, { passive: true });
    wrapper.addEventListener('touchend', function (e) {
      var dx = e.changedTouches[0].clientX - touchStartX;
      var dy = e.changedTouches[0].clientY - touchStartY;
      if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 30) {
        goTo(current + (dx < 0 ? 1 : -1));
      }
    }, { passive: true });

    // Respond to viewport resize
    window.matchMedia('(min-width: 1024px)').addEventListener('change', function (mq) {
      isDesktop = mq.matches;
      perPage   = isDesktop ? 2 : 4;
      pages     = Math.ceil(total / perPage);
      track.style.transform = '';
      current = 0;
      updateDots();
      updateArrows();
    });

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
