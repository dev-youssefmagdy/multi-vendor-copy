{{--
  Scroll-reveal ("WOW") animation for the Elora / Souqify home pages.

  Every direct child of the home page's [data-home-root] container (i.e. each
  home section) that starts below the fold gets WOW.js + Animate.css, so it
  fades up as it scrolls into view. Sections already on screen at load (the
  hero) are left alone so the first paint never flashes, and nothing animates
  for visitors who prefer reduced motion.
--}}
@if (request()->routeIs('tenant.home', 'tenant.path.home', 'preview'))
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
  <style>
    /* Animate.css's fadeInUp travels the element's full height, which is far too
       much for a tall home section - rise 40px instead. */
    @keyframes sfRevealUp {
      from { opacity: 0; transform: translate3d(0, 40px, 0); }
      to   { opacity: 1; transform: none; }
    }
    .animate__animated.sf-reveal-up {
      animation-name: sfRevealUp;
      --animate-duration: .8s;
    }
  </style>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/wow/1.1.2/wow.min.js" defer></script>
  <script>
    (function () {
      if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

      var SKIP = ['STYLE', 'SCRIPT', 'TEMPLATE', 'LINK', 'NOSCRIPT'];

      function markSections() {
        var viewportBottom = window.innerHeight;

        document.querySelectorAll('[data-home-root]').forEach(function (root) {
          Array.prototype.forEach.call(root.children, function (el) {
            if (SKIP.indexOf(el.tagName) !== -1 || el.hasAttribute('data-reveal-checked')) return;
            el.setAttribute('data-reveal-checked', '');

            var rect = el.getBoundingClientRect();
            var position = window.getComputedStyle(el).position;
            if (rect.height === 0 || position === 'fixed' || position === 'sticky') return;
            if (rect.top < viewportBottom) return; // already on screen: show as-is

            el.classList.add('wow', 'sf-reveal-up');
          });
        });
      }

      function start() {
        if (!window.WOW || !document.querySelector('[data-home-root]')) return;
        markSections();
        new window.WOW({
          boxClass: 'wow',
          animateClass: 'animate__animated',
          offset: 60,
          mobile: true,
          live: false,
        }).init();
      }

      window.addEventListener('load', start);
      document.addEventListener('livewire:navigated', start);
    })();
  </script>
@endif
