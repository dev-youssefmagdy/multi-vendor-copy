(function () {
        var apiUrl = window.__eloraHomeConfig.tabbedApiUrl;
        var currentTab = 'flash';
        var nextPage   = 2;
        var loading    = false;
        var hasMore    = window.__eloraHomeConfig.hasMoreFlash;
        var wrapper    = document.getElementById('tabbed-products-wrapper');
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
                if (loading) return;
                if (!hasMore) {
                    // Nothing left to append: open the full listing for the active tab
                    var active = document.querySelector('.tabbed-pill[data-tab="' + currentTab + '"]');
                    if (active && active.dataset.seeAll) window.location.href = active.dataset.seeAll;
                    return;
                }
                fetchTab(currentTab, nextPage, true);
            });
        }
    })();



    (function () {
    var eloraProductSliderConfig = {
        slidesPerView: 'auto',
        slidesPerGroup: 1,
        spaceBetween: 16,
        freeMode: false,
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

    // Desktop (lg+) slides-per-view comes from the section's data-per-view attribute;
    // below lg the slides keep their fixed Tailwind widths ('auto').
    function eloraProductSliderOptions(element) {
        var options = Object.assign({}, eloraProductSliderConfig);
        var perView = parseFloat(element.dataset.perView);
        var perViewMobile = parseFloat(element.dataset.perViewMobile);
        if (perView > 0 || perViewMobile > 0) {
            options.breakpoints = {
                0: perViewMobile > 0 ? { slidesPerView: perViewMobile, spaceBetween: 12 } : { slidesPerView: 'auto' },
                640: { slidesPerView: 'auto', spaceBetween: 16 },
                1024: { slidesPerView: perView > 0 ? perView : 'auto' },
            };
        }
        // Mobile renders these sections as a 2-column grid (see home.css); the carousel starts at sm.
        if (element.hasAttribute('data-mobile-grid')) {
            options.breakpoints = Object.assign({}, options.breakpoints || {}, {
                0: { slidesPerView: 'auto', enabled: false },
                640: { slidesPerView: 'auto', enabled: true },
            });
        }
        // Desktop prev/next arrows (themes/elora/pages/_carousel-nav) placed inside the carousel
        var prevBtn = element.querySelector(':scope > .elora-carousel-prev');
        var nextBtn = element.querySelector(':scope > .elora-carousel-next');
        if (prevBtn && nextBtn) {
            options.navigation = { prevEl: prevBtn, nextEl: nextBtn };
        }
        if (element.dataset.loadMore === 'all-products') {
            options.on = {
                reachEnd: function () {
                    if (typeof window.eloraLoadMoreAllProducts === 'function') {
                        window.eloraLoadMoreAllProducts();
                    }
                },
            };
        }
        return options;
    }

    function eloraInitProductSliders() {
        eloraDestroySwiper('.product-slid-swiper');

        document.querySelectorAll('.product-slid-swiper').forEach(function (element) {
            new Swiper(element, eloraProductSliderOptions(element));
        });
    }

    function eloraInitSwipers() {
        eloraDestroySwiper('.hero-slider');
        eloraDestroySwiper('.categories-slide');
        eloraDestroySwiper('.category-swiper-2row');
        eloraInitProductSliders();

        var heroElement = document.querySelector('.hero-slider');
        if (heroElement) {
            var heroSlideCount = heroElement.querySelectorAll('.swiper-slide').length;
            new Swiper(heroElement, {
                loop: heroSlideCount > 1,
                grabCursor: true,
                speed: 600,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
            });
        }

        document.querySelectorAll('.category-swiper-2row').forEach(function (element) {
            new Swiper(element, {
                slidesPerView: 6,
                slidesPerGroup: 1,
                spaceBetween: 8,
                freeMode: false,
                grabCursor: true,
                rtl: document.documentElement.dir === 'rtl',
                breakpoints: {
                    640: { slidesPerView: 'auto', spaceBetween: 16 },
                    1024: { slidesPerView: 11, spaceBetween: 32 },
                },
            });
        });

        var categoriesElement = document.querySelector('.categories-slide');
        if (categoriesElement) {
            new Swiper(categoriesElement, {
                slidesPerView: 'auto',
                slidesPerGroup: 1,
                spaceBetween: 24,
                freeMode: false,
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
    const wrapper  = document.getElementById('all-products-wrapper');
    if (!wrapper) return;

    let nextPage = 2;
    let loading  = false;
    let hasMore  = window.__eloraHomeConfig.hasMoreProducts;

    window.eloraLoadMoreAllProducts = function () {
        if (loading || !hasMore) return;
        loading = true;
        fetch(`${apiUrl}?page=${nextPage}`)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                data.cards.forEach(function (html) {
                    const slide = document.createElement('div');
                    slide.className = 'swiper-slide w-[132px] sm:w-[182px] lg:w-[210px]';
                    slide.innerHTML = html;
                    wrapper.appendChild(slide);
                });
                hasMore  = data.has_more;
                nextPage = data.next_page;
                const swiperEl = wrapper.closest('.swiper');
                if (swiperEl && swiperEl.swiper) swiperEl.swiper.update();
                if (window.markFavoritedProducts) window.markFavoritedProducts();
            })
            .catch(function () {})
            .finally(function () {
                loading = false;
            });
    };
})();
