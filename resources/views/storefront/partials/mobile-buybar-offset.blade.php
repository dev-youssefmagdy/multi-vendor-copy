{{-- Lifts a product page's fixed mobile buy bar above the theme's bottom nav. --}}
<script>
(function () {
    function place() {
        var bar = document.querySelector('[data-mobile-buybar]');
        if (!bar) return;
        var nav = document.querySelector('#mobileBottomNav, .mobile-tabbar, nav.fixed[class*="bottom-0"]');
        var navH = nav && getComputedStyle(nav).display !== 'none' && getComputedStyle(nav).position === 'fixed' ? nav.offsetHeight : 0;
        var isFixed = getComputedStyle(bar).position === 'fixed';
        bar.style.bottom = isFixed ? navH + 'px' : '';
        document.body.style.paddingBottom = isFixed ? bar.offsetHeight + 'px' : '';
    }
    var nav = document.querySelector('#mobileBottomNav, .mobile-tabbar, nav.fixed[class*="bottom-0"]');
    if (nav && window.ResizeObserver) new ResizeObserver(place).observe(nav);
    window.addEventListener('resize', place);
    document.addEventListener('livewire:navigated', place);
    place();
})();
</script>
