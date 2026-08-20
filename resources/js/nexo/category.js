(function() {
    // Expose toggleMobileFilters globally
    window.toggleMobileFilters = function() {
        var el = document.getElementById('mobile-filter-drawer');
        var backdrop = document.getElementById('mobile-filter-backdrop');
        if (!el) return;
        var btn = document.querySelector('button[aria-controls="mobile-filter-drawer"]');
        var isHidden = el.classList.contains('-translate-x-full');
        if (isHidden) {
            el.classList.remove('-translate-x-full');
            if (backdrop) backdrop.classList.remove('hidden');
            document.documentElement.style.overflow = 'hidden';
            if (btn) btn.setAttribute('aria-expanded', 'true');
            el.setAttribute('aria-hidden', 'false');
        } else {
            el.classList.add('-translate-x-full');
            if (backdrop) backdrop.classList.add('hidden');
            document.documentElement.style.overflow = '';
            if (btn) btn.setAttribute('aria-expanded', 'false');
            el.setAttribute('aria-hidden', 'true');
        }
    };

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var el = document.getElementById('mobile-filter-drawer');
            if (el && !el.classList.contains('-translate-x-full')) {
                toggleMobileFilters();
            }
        }
    });
})();

(function () {
    var scrollObserver = null;

    function initInfiniteScroll() {
        // Disconnect previous observer so we don't double-observe after Livewire morphs the DOM
        if (scrollObserver) {
            scrollObserver.disconnect();
            scrollObserver = null;
        }

        var sentinel = document.getElementById('nexo-infinite-sentinel');
        var spinner  = document.getElementById('nexo-loading-spinner');
        if (!sentinel) return;

        var grid    = sentinel.previousElementSibling;
        var loading = false;

        function loadMore() {
            var d = sentinel.dataset;
            if (d.hasMore !== '1' || loading) return;
            loading = true;
            if (spinner) spinner.classList.remove('hidden');

            var params = new URLSearchParams({
                page:         d.nextPage,
                keyword:      d.keyword,
                sort:         d.sort,
                availability: d.availability,
                product_flag: d.productFlag,
                on_sale:      d.sale,
                ratings:      d.ratings,
                min:          d.min,
                max:          d.max,
            });

            var slug = d.slug || '';
            var url = '/categories-products/' + (slug ? encodeURIComponent(slug) : '') + '?' + params.toString();

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    // Re-fetch grid reference in case Livewire morphed it
                    var currentSentinel = document.getElementById('nexo-infinite-sentinel');
                    var currentGrid = currentSentinel ? currentSentinel.previousElementSibling : grid;

                    if (data.cards && data.cards.length && currentGrid) {
                        var frag = document.createDocumentFragment();
                        data.cards.forEach(function (html) {
                            var tmp = document.createElement('div');
                            tmp.innerHTML = html;
                            while (tmp.firstChild) frag.appendChild(tmp.firstChild);
                        });
                        currentGrid.appendChild(frag);
                    }
                    if (currentSentinel) {
                        currentSentinel.dataset.nextPage = parseInt(d.nextPage, 10) + 1;
                        currentSentinel.dataset.hasMore  = data.has_more ? '1' : '0';
                    }
                    if (!data.has_more && spinner) spinner.classList.add('hidden');
                    loading = false;
                })
                .catch(function () {
                    loading = false;
                    if (spinner) spinner.classList.add('hidden');
                });
        }

        if ('IntersectionObserver' in window) {
            scrollObserver = new IntersectionObserver(function (entries) {
                if (entries[0].isIntersecting) loadMore();
            }, { rootMargin: '200px' });
            scrollObserver.observe(sentinel);
        }
    }

    // Initialize on page load
    initInfiniteScroll();

    // Re-initialize after each Livewire reactive update (filter/sort changes morph the DOM)
    document.addEventListener('livewire:updated', initInfiniteScroll);
})();
