(function () {
        var apiUrl = window.__eloraHomeConfig.tabbedApiUrl;
        var currentTab = 'flash';
        var nextPage   = 2;
        var loading    = false;
        var hasMore    = window.__eloraHomeConfig.hasMoreFlash;
        var wrapper    = document.getElementById('tabbed-products-wrapper');
        var moreWrap   = document.getElementById('tabbed-products-more');
        var moreBtn    = document.getElementById('tabbed-products-more-btn');
        var pills      = document.querySelectorAll('.tabbed-pill');

        function activatePill(tab) {
            pills.forEach(function (p) {
                var active = p.dataset.tab === tab;
                p.classList.toggle('active', active);
                p.classList.toggle('bg-main', active);
                p.classList.toggle('text-white', active);
                p.classList.toggle('border-main', active);
                p.classList.toggle('border-main-light', !active);
                p.classList.toggle('text-main', !active);
            });
        }

        function fetchTab(tab, page, append) {
            loading = true;
            fetch(apiUrl + '?tab=' + tab + '&page=' + page)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!append) {
                        wrapper.innerHTML = '';
                    }
                    data.cards.forEach(function (html) {
                        var slide = document.createElement('div');
                        slide.className = 'swiper-slide w-[132px] sm:w-[182px] lg:w-[210px]';
                        slide.innerHTML = html;
                        wrapper.appendChild(slide);
                    });
                    if (data.cards.length === 0 && !append) {
                        var p = document.createElement('p');
                        p.className = 'text-sm text-gray-400 py-6';
                        p.textContent = window.__eloraHomeConfig.noProductsText;
                        wrapper.appendChild(p);
                    }
                    hasMore  = data.has_more;
                    nextPage = data.next_page;
                    if (moreWrap) moreWrap.style.display = hasMore ? '' : 'none';
                    if (typeof window.eloraInitProductSliders === 'function') {
                        window.eloraInitProductSliders();
                    }
                })
                .catch(function () {})
                .finally(function () {
                    loading = false;
                });
        }

        pills.forEach(function (pill) {
            pill.addEventListener('click', function () {
                var tab = pill.dataset.tab;
                if (tab === currentTab) return;
                currentTab = tab;
                nextPage   = 1;
                activatePill(tab);
                fetchTab(tab, 1, false);
            });
        });

        if (moreBtn) {
            moreBtn.addEventListener('click', function () {
                if (!hasMore || loading) return;
                fetchTab(currentTab, nextPage, true);
            });
        }
    })();



    (function () {
    var eloraProductSliderConfig = {
        slidesPerView: 'auto',
        spaceBetween: 16,
        freeMode: true,
        autoplay: {
            delay: 4000,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
        },
    };

    function eloraDestroySwiper(selector) {
        document.querySelectorAll(selector).forEach(function (element) {
            if (element.swiper) {
                element.swiper.destroy(true, true);
            }
        });
    }

    function eloraInitProductSliders() {
        eloraDestroySwiper('.product-slid-swiper');

        document.querySelectorAll('.product-slid-swiper').forEach(function (element) {
            new Swiper(element, eloraProductSliderConfig);
        });
    }

    function eloraInitSwipers() {
        eloraDestroySwiper('.hero-slider');
        eloraDestroySwiper('.categories-slide');
        eloraInitProductSliders();

        var heroElement = document.querySelector('.hero-slider');
        if (heroElement) {
            var heroSlideCount = heroElement.querySelectorAll('.swiper-slide').length;
            new Swiper(heroElement, {
                loop: heroSlideCount > 1,
                effect: 'fade',
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
            });
        }

        var categoriesElement = document.querySelector('.categories-slide');
        if (categoriesElement) {
            new Swiper(categoriesElement, {
                slidesPerView: 'auto',
                spaceBetween: 24,
                freeMode: true,
                autoplay: {
                    delay: 4000,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true,
                },
            });
        }
    }

    window.eloraInitProductSliders = eloraInitProductSliders;
    eloraInitSwipers();

    if (!window.__eloraSwiperNavigatedBound) {
        window.__eloraSwiperNavigatedBound = true;
        document.addEventListener('livewire:navigated', ()=> {
            eloraInitSwipers();
            Livewire.on('refresh-swiper', (el, component) => {
                eloraInitSwipers();
            });
        });
        document.addEventListener('livewire:updated', eloraInitSwipers);
    }
})();


(function() {
    var el = document.querySelector('.elora-fs-h');
    if (!el) return;
    var end = parseInt(el.dataset.countdown, 10) * 1000;
    var hEl = el;
    var mEl = document.querySelector('.elora-fs-m');
    var sEl = document.querySelector('.elora-fs-s');

    function tick() {
        var diff = Math.max(0, end - Date.now());
        var h = Math.floor(diff / 3600000);
        var m = Math.floor((diff % 3600000) / 60000);
        var s = Math.floor((diff % 60000) / 1000);
        hEl.textContent = String(h).padStart(2, '0') + 'h';
        mEl.textContent = String(m).padStart(2, '0') + 'm';
        sEl.textContent = String(s).padStart(2, '0') + 's';
        if (diff > 0) setTimeout(tick, 1000);
    }
    tick();
})();


(function () {
    const apiUrl   = window.__eloraHomeConfig.allProductsApiUrl;
    const grid     = document.getElementById('all-products-grid');
    const sentinel = document.getElementById('all-products-sentinel');
    const spinner  = document.getElementById('all-products-spinner');
    if (!grid || !sentinel) return;

    let nextPage = 2;
    let loading  = false;
    let hasMore  = window.__eloraHomeConfig.hasMoreProducts;

    function loadMore() {
        if (loading || !hasMore) return;
        loading = true;
        spinner.classList.remove('hidden');
        fetch(`${apiUrl}?page=${nextPage}`)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                data.cards.forEach(function (html) {
                    grid.insertAdjacentHTML('beforeend', html);
                });
                hasMore  = data.has_more;
                nextPage = data.next_page;
                if (!hasMore) observer.disconnect();
                if (window.markFavoritedProducts) window.markFavoritedProducts();
            })
            .catch(function () {})
            .finally(function () {
                loading = false;
                spinner.classList.add('hidden');
            });
    }

    const observer = new IntersectionObserver(function (entries) {
        if (entries[0].isIntersecting) loadMore();
    }, { rootMargin: '50px' });

    if (hasMore) observer.observe(sentinel);
})();

