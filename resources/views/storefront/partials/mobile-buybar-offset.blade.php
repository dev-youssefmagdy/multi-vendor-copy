{{-- Lifts a product page's fixed mobile buy bar above the theme's bottom nav. --}}
<script>
(function () {
    function place() {
        var bar = document.querySelector('[data-mobile-buybar]');
        if (!bar) return;
        var nav = document.querySelector('#mobileBottomNav, .mobile-tabbar, nav.fixed[class*="bottom-0"]');
        var navH = nav && getComputedStyle(nav).display !== 'none' && getComputedStyle(nav).position === 'fixed' ? nav.offsetHeight : 0;
        if (navH) {
            // Some navs (e.g. Elora v2) have a raised centre button that rises above the bar; keep it visible.
            var navTop = nav.getBoundingClientRect().top;
            nav.querySelectorAll('a, button').forEach(function (el) {
                navH = Math.max(navH, nav.offsetHeight + Math.ceil(navTop - el.getBoundingClientRect().top));
            });
        }
        var isFixed = getComputedStyle(bar).position === 'fixed';
        bar.style.bottom = isFixed ? navH + 'px' : '';
        document.body.style.paddingBottom = isFixed ? bar.offsetHeight + 'px' : '';
    }
    var nav = document.querySelector('#mobileBottomNav, .mobile-tabbar, nav.fixed[class*="bottom-0"]');
    if (nav && window.ResizeObserver) new ResizeObserver(place).observe(nav);
    window.addEventListener('resize', place);
    document.addEventListener('livewire:navigated', place);
    // Livewire morphs can drop the inline offset, so re-apply after each update.
    document.addEventListener('livewire:init', function () {
        Livewire.hook('commit', function (ctx) { ctx.succeed(function () { requestAnimationFrame(place); }); });
    });
    place();
})();
</script>
