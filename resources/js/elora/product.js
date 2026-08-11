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
            freeMode: true,
            watchSlidesProgress: true,
            slideActiveClass: 'swiper-slide-thumb-active',
        });
        var fsThumbsSwiper = new Swiper(".fs-product-preview-thumbs", {
            spaceBetween: 16,
            slidesPerView: 'auto',
            freeMode: true,
            watchSlidesProgress: true,
            slideActiveClass: 'swiper-slide-thumb-active',
        });
        var productSlid = new Swiper(".product-slid-swiper", {
            spaceBetween: 24,
            slidesPerView: "auto",
            freeMode: true,
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

            // ── Gallery ────────────────────────────────────────────────────────────
            function mountGallery(root) {
                if (!root) return;
                if (root.__mantiMounted) return;
                root.__mantiMounted = true;

                const rawItems = root.getAttribute('data-media-items');
                if (!rawItems) return;

                let mediaItems;
                try {
                    mediaItems = JSON.parse(rawItems);
                } catch {
                    return;
                }
                if (!mediaItems || !mediaItems.length) return;

                const stage = root.querySelector('[data-product-media-stage]');
                const dots = Array.from(root.querySelectorAll('[data-media-dot]'));
                const thumbs = Array.from(document.querySelectorAll('[data-product-thumbs] [data-media-index]'));
                let currentIdx = 0;
                let slideTimer = null;
                let isPaused = false;
                let activeVideo = null;
                let mobileFixedModal = null;
                const isMobile = () => window.matchMedia('(max-width: 1023px)').matches;

                function stopActiveVideo() {
                    if (activeVideo) {
                        try {
                            activeVideo.pause();
                        } catch {
                        }
                        activeVideo = null;
                    }
                }

                function destroyFixedModal() {
                    if (mobileFixedModal && mobileFixedModal.parentNode) {
                        mobileFixedModal.parentNode.removeChild(mobileFixedModal);
                        mobileFixedModal = null;
                    }
                }

                function showFixedVideoModal(item) {
                    if (mobileFixedModal) return; // already shown

                    const modal = document.createElement('div');
                    modal.id = 'mantiFixedVideoModal';
                    modal.style.cssText = [
                        'position:fixed',
                        'top:80px',
                        'right:12px',
                        'width:160px',
                        'height:220px',
                        'border-radius:18px',
                        'overflow:hidden',
                        'background:#111',
                        'box-shadow:0 8px 32px rgba(0,0,0,0.55)',
                        'border:1.5px solid rgba(255,255,255,0.22)',
                        'z-index:9999',
                        'display:flex',
                        'flex-direction:column',
                        'transform:translateY(-16px)',
                        'opacity:0',
                        'transition:opacity 0.3s ease,transform 0.3s ease',
                    ].join(';');

                    // Close button
                    const closeBtn = document.createElement('button');
                    closeBtn.innerHTML = '&times;';
                    closeBtn.style.cssText = [
                        'position:absolute',
                        'top:6px',
                        'right:8px',
                        'z-index:10',
                        'color:#fff',
                        'font-size:18px',
                        'font-weight:700',
                        'line-height:1',
                        'background:rgba(0,0,0,0.5)',
                        'border:none',
                        'border-radius:50%',
                        'width:24px',
                        'height:24px',
                        'display:flex',
                        'align-items:center',
                        'justify-content:center',
                        'cursor:pointer',
                        'padding:0',
                    ].join(';');
                    closeBtn.setAttribute('aria-label', window.trans('Close video'));
                    closeBtn.onclick = () => {
                        stopActiveVideo();
                        destroyFixedModal();
                    };

                    const vid = document.createElement('video');
                    vid.src = item.src;
                    if (item.poster) vid.poster = item.poster;
                    vid.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block;';
                    vid.autoplay = true;
                    vid.muted = true;
                    vid.loop = true;
                    vid.playsInline = true;
                    vid.setAttribute('playsinline', '');

                    modal.appendChild(vid);
                    modal.appendChild(closeBtn);
                    document.body.appendChild(modal);
                    mobileFixedModal = modal;
                    activeVideo = vid;

                    // Animate in
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            modal.style.opacity = '1';
                            modal.style.transform = 'translateY(0)';
                        });
                    });

                    vid.play().catch(() => {
                    });
                }

                function renderImage(item) {
                    if (!stage) return;
                    stage.innerHTML = '';
                    if (!item.src) {
                        stage.innerHTML =
                            '<div class="w-16 h-16 text-gray-300 flex items-center justify-center"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>';
                        return;
                    }
                    const img = document.createElement('img');
                    img.src = item.src;
                    img.alt = item.alt || '';
                    img.className =
                        'w-full h-full max-h-[420px] lg:max-h-[500px] object-contain transition-opacity duration-150';
                    stage.appendChild(img);
                }

                function renderVideo(item) {
                    if (!stage) {
                        showFixedVideoModal(item);
                        return;
                    }
                    stage.innerHTML = '';

                    // Always show poster in the gallery slider; the fixed floating widget handles playback
                    const poster = item.poster || '';
                    const bg = document.createElement('div');
                    bg.className = 'w-full h-full min-h-[320px] flex items-center justify-center bg-[#f5f5f5] relative';
                    if (poster) {
                        const img = document.createElement('img');
                        img.src = poster;
                        img.alt = window.trans('Product');
                        img.className = 'w-full h-full max-h-[420px] lg:max-h-[500px] object-contain';
                        bg.appendChild(img);
                    } else {
                        bg.innerHTML =
                            '<div class="w-12 h-12 rounded-full bg-black/20 flex items-center justify-center"><svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg></div>';
                    }
                    // Play icon overlay hint
                    const playHint = document.createElement('div');
                    playHint.style.cssText = 'position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;';
                    playHint.innerHTML = '<div style="width:48px;height:48px;border-radius:50%;background:rgba(0,0,0,0.35);display:flex;align-items:center;justify-content:center;"><svg width="20" height="20" fill="white" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg></div>';
                    bg.appendChild(playHint);
                    stage.appendChild(bg);

                    // Ensure the fixed modal is open (may already be open from page load)
                    showFixedVideoModal(item);
                }

                function showIndex(idx) {

                    currentIdx = ((idx % mediaItems.length) + mediaItems.length) % mediaItems.length;
                    const item = mediaItems[currentIdx];
                    if (item.type === 'video') {
                        renderVideo(item);
                    } else {
                        renderImage(item);
                    }
                    dots.forEach((d, i) => {
                        d.classList.toggle('bg-[#222]', i === currentIdx);
                        d.classList.toggle('bg-[#d1d5db]', i !== currentIdx);
                    });
                    thumbs.forEach(t => {
                        const ti = parseInt(t.getAttribute('data-media-index'), 10);
                        t.classList.toggle('border-[#222]', ti === currentIdx);
                        t.classList.toggle('border-transparent', ti !== currentIdx);
                    });
                }

                function restartInterval() {
                    if (slideTimer) clearInterval(slideTimer);
                    if (!isPaused) {
                        slideTimer = setInterval(() => {
                            if (!isPaused) showIndex(currentIdx + 1);
                        }, 5000);
                    }
                }

                // Initial render
                showIndex(0);
                restartInterval();

                // Show fixed video modal on page load if any video exists (all screen sizes)
                const firstVideo = mediaItems.find(m => m.type === 'video');
                if (firstVideo) {
                    setTimeout(() => showFixedVideoModal(firstVideo), 400);
                }

                // Nav buttons
                root.querySelector('[data-media-nav="prev"]')?.addEventListener('click', () => {
                    showIndex(currentIdx - 1);
                    restartInterval();
                });
                root.querySelector('[data-media-nav="next"]')?.addEventListener('click', () => {
                    showIndex(currentIdx + 1);
                    restartInterval();
                });

                // Dots
                dots.forEach(d => d.addEventListener('click', () => {
                    showIndex(parseInt(d.getAttribute('data-media-dot'), 10));
                    restartInterval();
                }));

                // Thumbnails
                thumbs.forEach(t => t.addEventListener('click', () => {
                    showIndex(parseInt(t.getAttribute('data-media-index'), 10));
                    restartInterval();
                }));

                // Touch swipe
                let touchStartX = 0;
                root.addEventListener('touchstart', e => {
                    touchStartX = e.changedTouches[0].clientX;
                }, {
                    passive: true
                });
                root.addEventListener('touchend', e => {
                    const delta = e.changedTouches[0].clientX - touchStartX;
                    if (Math.abs(delta) >= 30) {
                        showIndex(currentIdx + (delta < 0 ? 1 : -1));
                        restartInterval();
                    }
                }, {
                    passive: true
                });

                // Pause on hover
                root.addEventListener('mouseenter', () => {
                    isPaused = true;
                });
                root.addEventListener('mouseleave', () => {
                    isPaused = false;
                    restartInterval();
                });

                // Pause when tab hidden
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        isPaused = true;
                        if (slideTimer) clearInterval(slideTimer);
                    } else {
                        isPaused = false;
                        restartInterval();
                    }
                });

                // Re-render on mobile/desktop switch
                window.matchMedia('(max-width: 1023px)').addEventListener('change', () => {
                    stopActiveVideo();
                    destroyFixedModal();
                    showIndex(currentIdx);
                    const firstVid = mediaItems.find(m => m.type === 'video');
                    if (firstVid) setTimeout(() => showFixedVideoModal(firstVid), 300);
                });
            }

            // ── Init ───────────────────────────────────────────────────────────────
            function initProductPage() {
                bindCartModal();
                const slider = document.getElementById('mantiProductMediaSlider');

                if (slider) {
                    slider.__mantiMounted = false; // allow re-mount on navigation
                    mountGallery(slider);
                }
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
                var outline = document.getElementById('elora-fav-icon-outline');
                var filled  = document.getElementById('elora-fav-icon-filled');
                var btn     = document.getElementById('elora-fav-btn');
                if (!outline || !filled || !btn) return;
                if (favorited) {
                    outline.classList.add('hidden');
                    filled.classList.remove('hidden');
                    btn.style.color = '#FF4D00';
                } else {
                    filled.classList.add('hidden');
                    outline.classList.remove('hidden');
                    btn.style.color = '';
                }
            }

            // Expose toggle function globally
            window.eloraHeartToggle = function (btn) {
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
                        console.error('eloraHeartToggle: failed to parse fav data', e);
                    }
                }
            };

            // Initialise heart state on page load
            document.addEventListener('DOMContentLoaded', function () {
                var btn = document.getElementById('elora-fav-btn');
                if (!btn) return;
                var productId = btn.getAttribute('data-product-id');
                syncHeartUI(isFavorited(productId));
            });

            // Re-sync after Livewire navigations
            document.addEventListener('livewire:navigated', function () {
                var btn = document.getElementById('elora-fav-btn');
                if (!btn) return;
                var productId = btn.getAttribute('data-product-id');
                syncHeartUI(isFavorited(productId));
            });
        })();
