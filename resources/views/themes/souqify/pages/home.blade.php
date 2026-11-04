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
$trendingProds = $trendingNowProducts ?? collect();
$topProds = ($bestSelling ?? collect())->take(5);
$newArrivals = $newInProducts ?? collect();
$_flashAll = $flashProducts ?? collect();
$exploreProds = $_flashAll->isNotEmpty() ? $_flashAll : ($newInProducts ?? collect());
$featuredProds = $featuredProducts ?? collect();
$hotDealsProds = $flashProducts ?? collect();
$specialProds = $topRatedProducts ?? collect();
$recommendedProds = $recommendedProducts ?? collect();

$promoCardBanner = $banners->skip(5)->first() ?? $promoBanners->first() ?? $heroBanner;
$__featuredPromoSetting = app(\App\Services\Tenant\HomeViewData::class)->settings(['souqify_featured_promo_image_url'])['souqify_featured_promo_image_url'] ?? null;
$promoCardImage = ($__featuredPromoSetting ?: null) ?? $promoCardBanner?->image_path ??
'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80';

// Promo card discount: highest active flash-sale % among featured products, else static 30
// (only the featured_products section renders it — skip the per-product lookup otherwise)
$__promoSectionList = $homeSections ?? \App\Services\Tenant\PageBuilder\SectionRegistry::defaultsFor('souqify', 'home');
$promoFlashPct = in_array('featured_products', $__promoSectionList instanceof \Illuminate\Support\Collection ? $__promoSectionList->all() : (array) $__promoSectionList, true)
    ? $featuredProds
        ->map(fn($p) => $p->activeStorefrontFlashSale()?->discount_percentage)
        ->filter()
        ->max()
    : null;
$promoDiscountPct = $promoFlashPct ? (int) round((float) $promoFlashPct) : 30;

// Currency helpers
$currency = $currentCurrency ?? null;
$symbol = $currency->symbol ?? '$';
$rate = (float) ($currency->conversion_rate ?? 1);
@endphp

<div data-home-root>
    @include('themes.souqify.partials.mobile-home-header')

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
@endpush
