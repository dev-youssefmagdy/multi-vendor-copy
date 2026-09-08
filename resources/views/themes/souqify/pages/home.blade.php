@php
$banners = $banners ?? collect();
$heroBanner = $banners->first();
$sideBanners = $banners->skip(1)->take(2);
$promoBanners = $banners->skip(3)->take(2);
$rootCats = ($categories ?? collect())->take(10);
$allCats = $categories ?? collect();
$tabKey = $activeProductTab ?? 'cat-flash';

// Flash sale layout
$flash = $flashProducts ?? collect();
$flashHero = $flash->first();
$flashLeft = $flash->skip(1)->take(3);
$flashRight = $flash->skip(4)->take(3);
$flashSaleEnd = optional(($flashSales ?? collect())->first())->end_date;

// Section collections mapped from available component data
$trendingProds = $bestSelling ?? collect();
$topProds = ($bestSelling ?? collect())->take(5);
$newArrivals = $newInProducts ?? collect();
$_flashAll = $flashProducts ?? collect();
$exploreProds = $_flashAll->isNotEmpty() ? $_flashAll : ($newInProducts ?? collect());
$featuredProds = $bestSelling ?? collect();
$hotDealsProds = $flashProducts ?? collect();
$specialProds = $topRatedProducts ?? collect();
$recommendedProds = ($bestSelling ?? collect())->slice(5)->values();

$promoCardBanner = $banners->skip(5)->first() ?? $promoBanners->first() ?? $heroBanner;
$promoCardImage = $promoCardBanner?->image_url ??
'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80';

// Promo card discount: highest active flash-sale % among featured products, else static 30
$promoFlashPct = $featuredProds
    ->map(fn($p) => $p->activeStorefrontFlashSale()?->discount_percentage)
    ->filter()
    ->max();
$promoDiscountPct = $promoFlashPct ? (int) round((float) $promoFlashPct) : 30;

// Currency helpers
$currency = $currentCurrency ?? null;
$symbol = $currency->symbol ?? '$';
$rate = (float) ($currency->conversion_rate ?? 1);
@endphp

<div>
    <!--========= mobile header ==========  -->
    <div class="lg:hidden text-white bg-gradient-to-r from-[#002562] via-[#144AA6] to-[#003387] mb-14 pt-6 px-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <!-- Mobile menu toggle -->
                <button onclick="openMobileMenu()" class="lg:hidden p-2 -ml-2" aria-label="{{ __('Open menu') }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <!-- Logo -->
                <a href="{{ route('tenant.home') }}" class="flex items-center gap-2 shrink-0">
                    <x-storefront-logo :storeName="$storeName" class="h-8 sm:h-10 lg:h-12 w-auto" />
                </a>
            </div>
            <div class="flex items-center gap-2">
                <!-- Currency / Language -->
                <button type="button"
                    onclick="event.preventDefault(); Livewire.dispatch('open-locale-modal', { tab: 'currency' })"
                    class="flex items-center justify-center text-main transition-colors bg-white text-black rounded-full w-10 h-10">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M3 12h18M12 3c2.5 2.6 3.75 5.7 3.75 9s-1.25 6.4-3.75 9c-2.5-2.6-3.75-5.7-3.75-9S9.5 5.6 12 3z" />
                    </svg>
                </button>
                <!-- cart -->
                <a href="{{ route('tenant.storefront.cart') }}"
                    class="relative flex items-center justify-center text-main transition-colors bg-white text-black rounded-full w-10 h-10">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M3.71 5.4H18.924C20.302 5.4 21.297 6.67 20.919 7.948L19.265 13.548C19.01 14.408 18.196 15 17.27 15H8.112C7.185 15 6.37 14.407 6.116 13.548L3.71 5.4ZM3.71 5.4L3 3M16.5 21C16.8978 21 17.2794 20.842 17.5607 20.5607C17.842 20.2794 18 19.8978 18 19.5C18 19.1022 17.842 18.7206 17.5607 18.4393C17.2794 18.158 16.8978 18 16.5 18C16.1022 18 15.7206 18.158 15.4393 18.4393C15.158 18.7206 15 19.1022 15 19.5C15 19.8978 15.158 20.2794 15.4393 20.5607C15.7206 20.842 16.1022 21 16.5 21ZM8.5 21C8.89782 21 9.27936 20.842 9.56066 20.5607C9.84196 20.2794 10 19.8978 10 19.5C10 19.1022 9.84196 18.7206 9.56066 18.4393C9.27936 18.158 8.89782 18 8.5 18C8.10218 18 7.72064 18.158 7.43934 18.4393C7.15804 18.7206 7 19.1022 7 19.5C7 19.8978 7.15804 20.2794 7.43934 20.5607C7.72064 20.842 8.10218 21 8.5 21Z"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span id="souqify-cart-badge-mobile" class="souqify-cart-badge absolute -top-1 -end-1 bg-blue-700 text-white text-[10px] rounded-full min-w-[16px] h-[16px] px-1 flex items-center justify-center font-semibold leading-none {{ $cartCount > 0 ? '' : 'hidden' }}">{{ $cartCount }}</span>
                </a>
            </div>
        </div>
        <!-- Mobile search -->
        <div class="translate-y-1/2 relative z-50 drop-shadow-2xl px-4 sm:px-6 lg:hidden">
            <form action="{{ route('tenant.storefront.search') }}" method="GET"
                data-autocomplete-url="{{ route('tenant.storefront.search.autocomplete') }}">
                <div class="souqify-search-inner flex items-center bg-white rounded-full px-3 sm:px-5 py-2 gap-2 h-14">
                    <svg class="w-5 h-5 text-neutral-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="q" value="{{ request('q') }}" autocomplete="off"
                        placeholder="{{ __('Search products...') }}"
                        class="flex-1 bg-transparent outline-none text-sm text-black" />
                </div>
            </form>
        </div>
    </div>


    @php
        $__homeSections = $homeSections ?? \App\Services\Tenant\PageBuilder\SectionRegistry::defaultsFor('souqify', 'home');
    @endphp
    @foreach ($__homeSections as $__section)
        @includeIf("themes.souqify.pages.home.sections.{$__section}")
    @endforeach
</div>

@push('scripts')
<script>
    (function() {
        var sqProductSliderConfig = {
            spaceBetween: 12,
            slidesPerView: "auto",
            freeMode: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
            },
            breakpoints: {
                1024: {
                    spaceBetween: 24
                },
            }
        };

        function sqDestroySwiper(selector) {
            document.querySelectorAll(selector).forEach(function(element) {
                if (element.swiper) {
                    element.swiper.destroy(true, true);
                }
            });
        }

        function sqInitSwipers() {
            sqDestroySwiper('.hero-slider');
            sqDestroySwiper('.product-slide-swiper');
            sqDestroySwiper('.categories-slide');
            sqDestroySwiper('.featured-product-slide');
            sqDestroySwiper('.banners-slide-swiper');

            var heroElement = document.querySelector('.hero-slider');
            if (heroElement) {
                new Swiper(heroElement, {
                    loop: true,
                    effect: 'fade',
                    autoplay: {
                        delay: 5000,
                        disableOnInteraction: false,
                    },
                });
            }

            document.querySelectorAll('.product-slide-swiper').forEach(function(element) {
                new Swiper(element, sqProductSliderConfig);
            });

            var categoriesElement = document.querySelector('.categories-slide');
            if (categoriesElement) {
                new Swiper(categoriesElement, {
                    loop: true,
                    spaceBetween: 16, // Updated to match the 8px gap from your CSS
                    slidesPerView: "auto",
                    freeMode: true,
                    navigation: {
                        nextEl: '.swiper-navigation-next',
                        prevEl: '.swiper-navigation-prev',
                    },
                    autoplay: {
                        delay: 4000,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true,
                    },
                });
            }

            document.querySelectorAll('.featured-product-slide').forEach(function(element) {
                new Swiper(element, {
                    spaceBetween: 24,
                    slidesPerView: "auto",
                    freeMode: true,
                    autoplay: {
                        delay: 4000,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true,
                    },
                });
            });

            var bannersElement = document.querySelector('.banners-slide-swiper');
            if (bannersElement) {
                new Swiper(bannersElement, {
                    spaceBetween: 24,
                    slidesPerView: 1.1,
                    freeMode: true,
                    autoplay: {
                        delay: 4000,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true,
                    },
                    breakpoints: {
                        375: {
                            slidesPerView: 1.2,
                            spaceBetween: 10
                        },
                        768: {
                            slidesPerView: 1.5,
                            spaceBetween: 10
                        },
                        1024: {
                            slidesPerView: 1.7,
                            spaceBetween: 10
                        },
                        1440: {
                            slidesPerView: 2,
                            spaceBetween: 10
                        },
                    }
                });
            }
        }

        window.sqInitSwipers = sqInitSwipers;
        sqInitSwipers();

        if (!window.__sqSwiperNavigatedBound) {
            window.__sqSwiperNavigatedBound = true;
            document.addEventListener('livewire:navigated', sqInitSwipers);
        }
    })();
</script>
<script>
    function sqNewsletterSubmit(e) {
        e.preventDefault();
        var form = e.target;
        var email = form.querySelector('[name="email"]').value;
        if (!email) return;
        Swal.fire({
            icon: 'success',
            title: '{{ __("Thank you!") }}',
            text: '{{ __("You have subscribed successfully.") }}',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
        form.reset();
    }

    function sqNavigateToProduct(event, url) {
        if (event.target.closest('a, button, input, textarea, select, label, [role="button"]')) {
            return;
        }

        if (window.Livewire && typeof window.Livewire.navigate === 'function') {
            window.Livewire.navigate(url);
            return;
        }

        window.location.href = url;
    }

    (function() {
        var fsEnd = {{ $flashSaleEnd ? $flashSaleEnd->timestamp * 1000 : 'Date.now() + 4 * 3600 * 1000' }};
        var fsTimer = null;

        function sqPad(n) {
            return String(n).padStart(2, '0');
        }

        function sqFsTick() {
            var diff = Math.max(0, Math.floor((fsEnd - Date.now()) / 1000));
            var h = Math.floor(diff / 3600);
            var m = Math.floor((diff % 3600) / 60);
            var s = diff % 60;
            var ph = sqPad(h),
                pm = sqPad(m),
                ps = sqPad(s);

            var eh = document.getElementById('sqFsHours');
            var em = document.getElementById('sqFsMins');
            var es = document.getElementById('sqFsSecs');
            if (eh) eh.textContent = ph;
            if (em) em.textContent = pm;
            if (es) es.textContent = ps;

            document.querySelectorAll('.sq-fsh2').forEach(function(el) {
                el.textContent = ph;
            });
            document.querySelectorAll('.sq-fsm2').forEach(function(el) {
                el.textContent = pm;
            });
            document.querySelectorAll('.sq-fss2').forEach(function(el) {
                el.textContent = ps;
            });
        }

        function sqFsInit() {
            clearInterval(fsTimer);
            sqFsTick();
            fsTimer = setInterval(sqFsTick, 1000);
        }

        sqFsInit();
        document.addEventListener('livewire:navigated', sqFsInit);
        document.addEventListener('livewire:navigating', function() {
            clearInterval(fsTimer);
        });
    })();
</script>
<script>
    // ── Recommended Products — infinite scroll ─────────────────────────────
    (function() {
        var _grid = null;
        var _sentinel = null;
        var _loader = null;
        var _observer = null;
        var _loading = false;
        var _nextPage = 1;
        var _hasMore = true;
        var _apiUrl = null;

        function sqRecInit() {
            _grid = document.getElementById('sqRecommendedGrid');
            _sentinel = document.getElementById('sqRecommendedSentinel');
            _loader = document.getElementById('sqRecommendedLoader');
            var section = document.getElementById('sqRecommendedSection');

            if (!_grid || !section) return;

            _apiUrl = section.dataset.apiUrl;
            _nextPage = 1;
            _hasMore = true;
            _loading = false;
            _grid.innerHTML = '';
            if (_sentinel) _sentinel.style.display = '';

            if (_observer) {
                _observer.disconnect();
                _observer = null;
            }

            _observer = new IntersectionObserver(function(entries) {
                if (!entries[0].isIntersecting || _loading || !_hasMore) return;
                sqRecLoadPage();
            }, {
                rootMargin: '500px'
            });

            if (_sentinel) _observer.observe(_sentinel);
        }

        function sqRecLoadPage() {
            if (_loading || !_hasMore) return;
            _loading = true;

            if (_loader) {
                _loader.style.display = 'flex';
            }

            var url = _apiUrl + (_apiUrl.indexOf('?') === -1 ? '?' : '&') + 'page=' + _nextPage;

            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(data) {
                    if (data.cards && data.cards.length && _grid) {
                        data.cards.forEach(function(html) {
                            var wrap = document.createElement('div');
                            wrap.innerHTML = html.trim();
                            var card = wrap.firstElementChild;
                            if (card) _grid.appendChild(card);
                        });
                    }
                    _hasMore = !!data.has_more;
                    _nextPage = data.next_page || (_nextPage + 1);

                    if (!_hasMore && _sentinel) {
                        _sentinel.style.display = 'none';
                    }
                })
                .catch(function() {
                    /* silently fail */
                })
                .finally(function() {
                    _loading = false;
                    if (_loader) {
                        _loader.style.display = 'none';
                    }
                });
        }

        // Init on first load
        sqRecInit();

        // Re-init on Livewire SPA navigation
        document.addEventListener('livewire:navigated', sqRecInit);
    })();
</script>
@endpush
