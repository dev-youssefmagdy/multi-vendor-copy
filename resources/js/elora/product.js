import { mountVideoPip } from '../storefront/video-pip';

window.eloraOpenDeliveryModal = function () {
            var el = document.getElementById('elora-delivery-modal-backdrop');
            if (el) {
                el.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        };

        window.eloraCloseDeliveryModal = function () {
            var el = document.getElementById('elora-delivery-modal-backdrop');
            if (el) {
                el.style.display = 'none';
                document.body.style.overflow = '';
            }
        };

        window.eloraOpenPrivacyModal = function () {
            var el = document.getElementById('elora-privacy-modal-backdrop');
            if (el) {
                el.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        };

        window.eloraClosePrivacyModal = function () {
            var el = document.getElementById('elora-privacy-modal-backdrop');
            if (el) {
                el.style.display = 'none';
                document.body.style.overflow = '';
            }
        };

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                window.eloraClosePrivacyModal();
                window.eloraCloseDeliveryModal();
            }
        });


        // Flash sale countdown
        (function () {
            var el = document.getElementById('product-flash-countdown');
            if (!el) return;
            var end = parseInt(el.dataset.countdown, 10) * 1000;

            function pad(n) {
                return String(n).padStart(2, '0');
            }

            function tick() {
                var diff = Math.max(0, end - Date.now());
                var h = Math.floor(diff / 3600000);
                var m = Math.floor((diff % 3600000) / 60000);
                var s = Math.floor((diff % 60000) / 1000);
                el.textContent = pad(h) + 'h : ' + pad(m) + 'm : ' + pad(s) + 's';
                if (diff > 0) setTimeout(tick, 1000);
            }

            tick();
        })();

        var thumbsSwiper = new Swiper(".product-preview-thumbs", {
            spaceBetween: 16,
            slidesPerView: 'auto',
            freeMode: false,
            watchSlidesProgress: true,
            slideActiveClass: 'swiper-slide-thumb-active',
        });
        var fsThumbsSwiper = new Swiper(".fs-product-preview-thumbs", {
            spaceBetween: 16,
            slidesPerView: 'auto',
            freeMode: false,
            watchSlidesProgress: true,
            slideActiveClass: 'swiper-slide-thumb-active',
        });
        var productSlid = new Swiper(".product-slid-swiper", {
            spaceBetween: 24,
            slidesPerView: "auto",
            freeMode: false,
        });

        function eloraStopAllVideos(swiperEl, videoClass) {
            swiperEl.querySelectorAll('.' + videoClass).forEach(function (v) {
                v.pause();
                v.currentTime = 0;
            });
        }

        function eloraGoToMediaIndex(index) {
            if (index === null || index === undefined) return;
            if (window.eloraMainSwiper) window.eloraMainSwiper.slideToLoop(index);
            if (window.eloraFsSwiper) window.eloraFsSwiper.slideToLoop(index);
        }
        window.eloraGoToMediaIndex = eloraGoToMediaIndex;

        const swiper = new Swiper('.product-preview-swiper', {
            loop: true,
            // autoplay: {
            //     delay: 5000,
            //     disableOnInteraction: false,
            //     pauseOnMouseEnter: true
            // },
            pagination: {
                el: '.swiper-pagination',
                type: 'fraction',
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
                addIcons: false,
            },
            thumbs: {
                swiper: thumbsSwiper,
                slideThumbActiveClass: 'swiper-slide-thumb-active',
            },
            on: {
                slideChange: function () {
                    eloraStopAllVideos(this.el, 'elora-gallery-video');
                    const activeSlide = this.slides[this.activeIndex];
                    if (activeSlide) {
                        const vid = activeSlide.querySelector('.elora-gallery-video');
                        if (vid) {
                            vid.play().catch(() => {
                            });
                        }
                    }
                },
                afterInit: function () {
                    const activeSlide = this.slides[this.activeIndex];
                    if (activeSlide) {
                        const vid = activeSlide.querySelector('.elora-gallery-video');
                        if (vid) {
                            vid.play().catch(() => {
                            });
                        }
                    }
                },
            },
        });
        window.eloraMainSwiper = swiper;

        // Floating video popup: shown while the gallery is scrolled out of view.
        (function () {
            const sourceVideo = swiper.el.querySelector('.swiper-slide:not(.swiper-slide-duplicate) .elora-gallery-video');
            if (!sourceVideo) return;
            const videoIdx = Number(sourceVideo.closest('.swiper-slide').dataset.swiperSlideIndex);

            mountVideoPip({
                anchor: swiper.el,
                video: {
                    src: sourceVideo.getAttribute('src'),
                    poster: sourceVideo.getAttribute('poster'),
                },
                goToVideo: () => eloraGoToMediaIndex(videoIdx),
                getGalleryVideo: () => {
                    const active = swiper.slides[swiper.activeIndex];
                    return (active && active.querySelector('.elora-gallery-video')) || sourceVideo;
                },
                pauseGalleryVideo: () => {
                    swiper.el.querySelectorAll('.elora-gallery-video').forEach((v) => v.pause());
                },
                closeLabel: window.trans('Close video'),
            });
        })();
        const fsSwiper = new Swiper('.fs-product-preview-swiper', {
            loop: true,
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
                addIcons: false,
            },
            thumbs: {
                swiper: fsThumbsSwiper,
                slideThumbActiveClass: 'swiper-slide-thumb-active',
            },
            on: {
                slideChange: function () {
                    eloraStopAllVideos(this.el, 'elora-fs-gallery-video');
                    const activeSlide = this.slides[this.activeIndex];
                    if (activeSlide) {
                        const vid = activeSlide.querySelector('.elora-fs-gallery-video');
                        if (vid) {
                            vid.play().catch(() => {
                            });
                        }
                    }
                },
            },
        });
        window.eloraFsSwiper = fsSwiper;

        // Thumbnail hover preview: hovering a thumb shows its image in the main slider.
        (function () {
            ['.product-preview-thumbs', '.fs-product-preview-thumbs'].forEach(function (selector) {
                document.querySelectorAll(selector + ' .swiper-slide').forEach(function (thumb, idx) {
                    thumb.addEventListener('mouseenter', function () {
                        eloraGoToMediaIndex(idx);
                    });
                });
            });
        })();

        (function () {
            'use strict';
            // Persists across re-mounts (e.g. Livewire re-renders) for the current page view only.
            let videoModalDismissed = false;
            // --- full screen images handler -------------------------------------------
            const productPreviewButton = document.getElementById('product-preview-button');
            const preview = document.getElementById('product-preview');
            const closePreviewButton = document.getElementById('close-product-preview-button');

            if (productPreviewButton && preview) {
                productPreviewButton.addEventListener('click', () => {
                    preview.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                });
            }

            if (closePreviewButton && preview) {
                closePreviewButton.addEventListener('click', () => {
                    preview.classList.add('hidden');
                    document.body.style.overflow = '';
                });
            }

            // ── Cart modal bridge ──────────────────────────────────────────────────
            function bindCartModal() {
                if (window.__mantiProductCartModalBound) return;
                window.__mantiProductCartModalBound = true;
                if (typeof Livewire !== 'undefined') {
                    Livewire.on('storefront-cart-added', ({
                                                              itemName,
                                                              qty
                                                          }) => {
                        if (typeof showCartToast === 'function') {
                            showCartToast(itemName || (window.__eloraProductConfig && window.__eloraProductConfig.textItem) || window.trans('Item'), qty || 1);
                        }
                    });
                }
            }

            // ── Description read more / show less ────────────────────────────────
            function initReadMore() {
                document.querySelectorAll('.desc-readmore:not([data-readmore-init])').forEach((wrap) => {
                    wrap.setAttribute('data-readmore-init', '1');
                    const btn = wrap.parentElement && wrap.parentElement.querySelector('.desc-readmore-btn');
                    const content = wrap.firstElementChild;
                    if (!btn || !content) return;

                    const lines = parseInt(wrap.getAttribute('data-lines') || '4', 10);
                    let lineHeight = parseFloat(getComputedStyle(content).lineHeight);
                    if (!lineHeight || Number.isNaN(lineHeight)) lineHeight = 20;
                    const collapsedHeight = Math.ceil(lineHeight * lines);
                    const fullHeight = wrap.scrollHeight;

                    if (fullHeight <= collapsedHeight + 8) {
                        btn.style.display = 'none';
                        return;
                    }

                    wrap.style.maxHeight = collapsedHeight + 'px';
                    wrap.classList.add('is-collapsed');

                    btn.addEventListener('click', () => {
                        const isCollapsed = wrap.classList.contains('is-collapsed');
                        if (isCollapsed) {
                            wrap.style.maxHeight = fullHeight + 'px';
                            wrap.classList.remove('is-collapsed');
                            btn.textContent = btn.getAttribute('data-less-text');
                        } else {
                            wrap.style.maxHeight = collapsedHeight + 'px';
                            wrap.classList.add('is-collapsed');
                            btn.textContent = btn.getAttribute('data-more-text');
                        }
                    });
                });
            }

            // ── Init ───────────────────────────────────────────────────────────────
            function initProductPage() {
                bindCartModal();
                initReadMore();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initProductPage);
            } else {
                initProductPage();
            }

            document.addEventListener('livewire:navigate', () => {
                // Clean up fixed video modal before Livewire navigation
                const existingModal = document.getElementById('mantiFixedVideoModal');
                if (existingModal) existingModal.remove();
                videoModalDismissed = false; // reset for the incoming page
            });

            document.addEventListener('livewire:navigated', () => {
                window.__mantiProductCartModalBound = false;
                initProductPage();
            });
        })();


        (function () {
            var shareUrl = '';

            window.eloraOpenShareModal = function () {
                var cfg = window.__eloraProductConfig || {};
                shareUrl = cfg.shareUrl || window.location.href;
                var modal = document.getElementById('elora-share-modal');
                var sheet = document.getElementById('elora-share-sheet');
                var urlText = document.getElementById('elora-share-url-text');
                if (!modal) return;

                var encoded = encodeURIComponent(shareUrl);
                var productName = encodeURIComponent(cfg.shareTitle || document.title || '');
                var productDesc = encodeURIComponent(cfg.shareDescription || '');
                var shareText = productName + (productDesc ? '%20%E2%80%94%20' + productDesc : '');

                document.getElementById('elora-share-whatsapp').href = 'https://wa.me/?text=' + shareText + '%20' + encoded;
                document.getElementById('elora-share-facebook').href = 'https://www.facebook.com/sharer/sharer.php?u=' + encoded;
                document.getElementById('elora-share-twitter').href = 'https://twitter.com/intent/tweet?url=' + encoded + '&text=' + productName;
                document.getElementById('elora-share-telegram').href = 'https://t.me/share/url?url=' + encoded + '&text=' + shareText;

                if (urlText) urlText.textContent = shareUrl;

                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        sheet.style.opacity = '1';
                        sheet.style.transform = 'translateY(0)';
                    });
                });
            };

            window.eloraCloseShareModal = function () {
                var modal = document.getElementById('elora-share-modal');
                var sheet = document.getElementById('elora-share-sheet');
                if (!modal) return;
                sheet.style.opacity = '0';
                sheet.style.transform = 'translateY(16px)';
                setTimeout(function () {
                    modal.classList.add('hidden');
                    document.body.style.overflow = '';
                }, 200);
            };

            window.eloraCopyShareUrl = function () {
                if (!shareUrl) return;
                var btn = document.getElementById('elora-copy-btn');
                var textCopied = window.__eloraProductConfig && window.__eloraProductConfig.textCopied || window.trans('Copied!');
                var textCopy   = window.__eloraProductConfig && window.__eloraProductConfig.textCopy   || window.trans('Copy');
                function showCopied() {
                    if (btn) {
                        btn.textContent = textCopied;
                        setTimeout(function () { btn.textContent = textCopy; }, 2000);
                    }
                }
                navigator.clipboard.writeText(shareUrl).then(showCopied).catch(function () {
                    // Fallback for older browsers
                    var ta = document.createElement('textarea');
                    ta.value = shareUrl;
                    ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px';
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    showCopied();
                });
            };

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') window.eloraCloseShareModal();
            });
        })();


        (function () {
            var STORAGE_KEY = 'elora_favorites';

            function getFavs() {
                try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); } catch (e) { return []; }
            }

            function saveFavs(favs) {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(favs));
            }

            function isFavorited(productId) {
                return getFavs().some(function (f) { return String(f.id) === String(productId); });
            }

            function syncHeartUI(favorited) {
                document.querySelectorAll('[data-fav-btn]').forEach(function (btn) {
                    var outline = btn.querySelector('.elora-fav-icon-outline');
                    var filled  = btn.querySelector('.elora-fav-icon-filled');
                    if (!outline || !filled) return;
                    outline.classList.toggle('hidden', favorited);
                    filled.classList.toggle('hidden', !favorited);
                    btn.style.color = favorited ? '#FF4D00' : '';
                });
            }

            // Expose toggle function globally
            window.eloraProductHeartToggle = function (btn) {
                var isLoggedIn = btn.getAttribute('data-logged-in') === 'true';

                if (!isLoggedIn) {
                    if (typeof showStorefrontToast === 'function') {
                        showStorefrontToast(window.__eloraProductConfig && window.__eloraProductConfig.textLoginFavorites || window.trans('Please log in to add products to your favorites.'), 'warning');
                    }
                    return;
                }

                var productId = btn.getAttribute('data-product-id');
                var favs      = getFavs();
                var idx       = favs.findIndex(function (f) { return String(f.id) === String(productId); });

                if (idx !== -1) {
                    // Already favorited → remove
                    favs.splice(idx, 1);
                    saveFavs(favs);
                    syncHeartUI(false);
                    if (typeof showStorefrontToast === 'function') {
                        showStorefrontToast(window.__eloraProductConfig && window.__eloraProductConfig.textRemovedFav || window.trans('Removed from favorites.'), 'info');
                    }
                } else {
                    // Not favorited → add
                    try {
                        var data = JSON.parse(btn.getAttribute('data-fav') || '{}');
                        data.added = Math.floor(Date.now() / 1000);
                        favs.unshift(data);
                        saveFavs(favs);
                        syncHeartUI(true);
                        if (typeof showStorefrontToast === 'function') {
                            showStorefrontToast(window.__eloraProductConfig && window.__eloraProductConfig.textAddedFav || window.trans('Added to favorites!'), 'success');
                        }
                    } catch (e) {
                        console.error('eloraProductHeartToggle: failed to parse fav data', e);
                    }
                }
            };

            // Initialise heart state on page load
            document.addEventListener('DOMContentLoaded', function () {
                var btn = document.querySelector('[data-fav-btn]');
                if (!btn) return;
                var productId = btn.getAttribute('data-product-id');
                syncHeartUI(isFavorited(productId));
            });

            // Re-sync after Livewire navigations
            document.addEventListener('livewire:navigated', function () {
                var btn = document.querySelector('[data-fav-btn]');
                if (!btn) return;
                var productId = btn.getAttribute('data-product-id');
                syncHeartUI(isFavorited(productId));
            });
        })();
