{{--
    Lifts a product page's fixed mobile buy bar above the theme's bottom nav and reserves the bar's
    height at the end of the page (its own spacer element, so it can't be overwritten by anything
    else that writes body padding, e.g. Laravel Debugbar). The nav's own clearance comes from the
    theme footer; this only adds the buy bar on top of it.
--}}
<script>
(function () {
    var CLEARANCE = 16;

    function spacer() {
        var el = document.getElementById('buybar-spacer');
        if (!el) {
            el = document.createElement('div');
            el.id = 'buybar-spacer';
            el.setAttribute('aria-hidden', 'true');
        }
        // keep it as the last thing on the page
        if (el.parentNode !== document.body || el.nextElementSibling) document.body.appendChild(el);
        return el;
    }

    function navEl() {
        return document.querySelector('#mobileBottomNav, .mobile-tabbar, nav.fixed[class*="bottom-0"]');
    }

    // Everything fixed to the bottom of the viewport (theme tab bar + buy bar).
    function bottomBars() {
        var out = [];
        document.querySelectorAll('body *').forEach(function (el) {
            var cs = getComputedStyle(el);
            if (cs.position !== 'fixed' || cs.display === 'none' || cs.visibility === 'hidden') return;
            if (/debugbar|drawer|overlay|modal|toast/i.test(el.className + ' ' + el.id)) return;
            var r = el.getBoundingClientRect();
            if (r.height < 30 || r.height > 160 || r.width < 200) return;
            if (r.bottom < innerHeight - 200 || r.top < innerHeight / 2) return;
            out.push(el);
        });
        return out;
    }

    // Space the theme already leaves under the last visible footer text (or the body padding when
    // the theme has no mobile footer), so only the missing part is added.
    function slack(spacerEl) {
        var docH = document.documentElement.scrollHeight;
        var ft = document.querySelector('footer');
        if (ft && ft.offsetHeight > 0) {
            var walker = document.createTreeWalker(ft, NodeFilter.SHOW_TEXT), last = null, n;
            while ((n = walker.nextNode())) {
                if (n.textContent.trim() && n.parentElement.offsetHeight) last = n.parentElement;
            }
            if (last) return docH - (last.getBoundingClientRect().bottom + window.scrollY);
        }
        return parseFloat(getComputedStyle(document.body).paddingBottom) || 0;
    }

    function place() {
        var el = spacer();
        var bar = document.querySelector('[data-mobile-buybar]');
        el.style.height = '0px';
        if (!bar) return;

        var nav = navEl();
        var navH = nav && getComputedStyle(nav).display !== 'none' && getComputedStyle(nav).position === 'fixed' ? nav.offsetHeight : 0;
        if (navH) {
            // Some navs (e.g. Elora v2) have a raised centre button that rises above the bar; keep it visible.
            var navTop = nav.getBoundingClientRect().top;
            nav.querySelectorAll('a, button').forEach(function (b) {
                navH = Math.max(navH, nav.offsetHeight + Math.ceil(navTop - b.getBoundingClientRect().top));
            });
        }
        var isFixed = getComputedStyle(bar).position === 'fixed';
        bar.style.bottom = isFixed ? navH + 'px' : '';
        if (!isFixed) return;

        var bars = bottomBars();
        if (!bars.length) return;
        var occupied = innerHeight - Math.min.apply(null, bars.map(function (b) { return b.getBoundingClientRect().top; }));
        el.style.height = Math.max(0, Math.ceil(occupied + CLEARANCE - slack(el))) + 'px';
    }

    var nav = navEl();
    if (window.ResizeObserver) {
        var ro = new ResizeObserver(place);
        if (nav) ro.observe(nav);
        var buybar = document.querySelector('[data-mobile-buybar]');
        if (buybar) ro.observe(buybar);
    }
    window.addEventListener('resize', place);
    window.addEventListener('load', place);
    document.addEventListener('livewire:navigated', place);
    // Livewire morphs can drop the inline offset, so re-apply after each update.
    document.addEventListener('livewire:init', function () {
        Livewire.hook('commit', function (ctx) { ctx.succeed(function () { requestAnimationFrame(place); }); });
    });
    place();
})();
</script>
