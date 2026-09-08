
(function () {
    'use strict';

    // ── Cart modal bridge ──────────────────────────────────────────────────
    function bindCartModal() {
        if (window.__mantiProductCartModalBound) return;
        window.__mantiProductCartModalBound = true;
        if (typeof Livewire !== 'undefined') {
            Livewire.on('storefront-cart-added', ({ itemName, qty }) => {
                if (typeof showCartToast === 'function') {
                    showCartToast(itemName || window.trans('Item'), qty || 1);
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
        try { mediaItems = JSON.parse(rawItems); } catch { return; }
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
                try { activeVideo.pause(); } catch { }
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
                'bottom:90px',
                'right:12px',
                'width:140px',
                'height:190px',
                'border-radius:16px',
                'overflow:hidden',
                'background:#111',
                'box-shadow:0 8px 32px rgba(0,0,0,0.45)',
                'border:1.5px solid rgba(255,255,255,0.18)',
                'z-index:9999',
                'display:flex',
                'flex-direction:column',
                'transform:translateY(20px)',
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

            vid.play().catch(() => { });
        }

        function renderImage(item) {
            stage.innerHTML = '';
            if (!item.src) {
                stage.innerHTML = '<div class="w-16 h-16 text-gray-300 flex items-center justify-center"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>';
                return;
            }
            const img = document.createElement('img');
            img.src = item.src;
            img.alt = item.alt || '';
            img.className = 'w-[500px] h-[500px] max-w-full max-h-full object-contain transition-opacity duration-150';
            stage.appendChild(img);
        }

        function renderVideo(item) {
            stage.innerHTML = '';

            if (isMobile()) {
                // Mobile: keep slider showing poster image; video plays in fixed modal
                const poster = item.poster || '';
                const bg = document.createElement('div');
                bg.className = 'w-full h-full min-h-[320px] flex items-center justify-center bg-[#f5f5f5]';
                if (poster) {
                    const img = document.createElement('img');
                    img.src = poster;
                    img.alt = window.trans('Product');
                    img.className = 'w-full h-full max-h-[420px] object-contain';
                    bg.appendChild(img);
                } else {
                    bg.innerHTML = '<div class="w-12 h-12 rounded-full bg-black/20 flex items-center justify-center"><svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg></div>';
                }
                stage.appendChild(bg);
                showFixedVideoModal(item);
            } else {
                // Desktop: full-stage video
                stopActiveVideo();
                const vid = document.createElement('video');
                vid.src = item.src;
                vid.className = 'w-full h-full max-h-[500px] object-contain';
                vid.autoplay = true;
                vid.muted = true;
                vid.loop = true;
                vid.controls = true;
                vid.playsInline = true;
                vid.setAttribute('playsinline', '');
                stage.appendChild(vid);
                activeVideo = vid;
                vid.play().catch(() => { });
            }
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

        // On mobile: show fixed video modal only after document fully loads
        if (isMobile()) {
            const firstVideo = mediaItems.find(m => m.type === 'video');
            if (firstVideo) {
                if (document.readyState === 'complete') {
                    showFixedVideoModal(firstVideo);
                } else {
                    window.addEventListener('load', () => showFixedVideoModal(firstVideo), { once: true });
                }
            }
        }

        // Nav buttons
        root.querySelector('[data-media-nav="prev"]')?.addEventListener('click', () => {
            showIndex(currentIdx - 1); restartInterval();
        });
        root.querySelector('[data-media-nav="next"]')?.addEventListener('click', () => {
            showIndex(currentIdx + 1); restartInterval();
        });

        // Dots
        dots.forEach(d => d.addEventListener('click', () => {
            showIndex(parseInt(d.getAttribute('data-media-dot'), 10));
            restartInterval();
        }));

        // Thumbnails
        thumbs.forEach(t => {
            t.addEventListener('click', () => {
                showIndex(parseInt(t.getAttribute('data-media-index'), 10));
                restartInterval();
            });
            // Hover preview: show the thumb's image in the main stage without clicking
            t.addEventListener('mouseenter', () => {
                showIndex(parseInt(t.getAttribute('data-media-index'), 10));
            });
        });

        // Expose for variant-selection sync (jump gallery to the selected variant's image)
        window.mantiShowMediaIndex = function (idx) {
            showIndex(idx);
            restartInterval();
        };

        // Touch swipe
        let touchStartX = 0;
        root.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].clientX; }, { passive: true });
        root.addEventListener('touchend', e => {
            const delta = e.changedTouches[0].clientX - touchStartX;
            if (Math.abs(delta) >= 30) { showIndex(currentIdx + (delta < 0 ? 1 : -1)); restartInterval(); }
        }, { passive: true });

        // Pause on hover
        root.addEventListener('mouseenter', () => { isPaused = true; });
        root.addEventListener('mouseleave', () => { isPaused = false; restartInterval(); });

        // Pause when tab hidden
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) { isPaused = true; if (slideTimer) clearInterval(slideTimer); }
            else { isPaused = false; restartInterval(); }
        });

        // Re-render on mobile/desktop switch
        window.matchMedia('(max-width: 1023px)').addEventListener('change', () => {
            stopActiveVideo();
            destroyFixedModal();
            showIndex(currentIdx);
            if (isMobile()) {
                const firstVideo = mediaItems.find(m => m.type === 'video');
                if (firstVideo) setTimeout(() => showFixedVideoModal(firstVideo), 300);
            }
        });
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
        const slider = document.getElementById('mantiProductMediaSlider');
        if (slider) {
            slider.__mantiMounted = false; // allow re-mount on navigation
            mountGallery(slider);
        }
        initReadMore();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProductPage);
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
