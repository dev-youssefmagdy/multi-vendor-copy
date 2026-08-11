/* Theme image lazy-loader — IntersectionObserver polyfill for loading="lazy" */
(function () {
    'use strict';

    if (!window.IntersectionObserver) {
        // Browser doesn't support IO — images already have loading="lazy" so just return
        return;
    }

    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            var img = entry.target;
            var src = img.getAttribute('data-lazy-src');
            if (src) {
                img.src = src;
                img.removeAttribute('data-lazy-src');
            }
            img.removeAttribute('data-lazy-pending');
            io.unobserve(img);
        });
    }, { rootMargin: '200px 0px', threshold: 0 });

    function observe(root) {
        (root || document).querySelectorAll('img[loading="lazy"]').forEach(function (img) {
            // Skip already-observed or already-loaded images
            if (img.hasAttribute('data-lazy-pending') || img.complete) return;
            img.setAttribute('data-lazy-pending', '1');
            io.observe(img);
        });
    }

    document.addEventListener('DOMContentLoaded', function () { observe(); });
    document.addEventListener('livewire:navigated', function () { observe(); });
    document.addEventListener('livewire:updated', function () { observe(); });
}());
