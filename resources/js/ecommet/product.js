import { mountVideoPip } from '../storefront/video-pip';

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

        function stopActiveVideo() {
            if (activeVideo) {
                try { activeVideo.pause(); } catch { }
                activeVideo = null;
            }
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
            stopActiveVideo();
            const vid = document.createElement('video');
            vid.src = item.src;
            if (item.poster) vid.poster = item.poster;
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

        // Floating video popup: shown while the gallery is scrolled out of view.
        const firstVideoIdx = mediaItems.findIndex(m => m.type === 'video');
        if (firstVideoIdx !== -1) {
            mountVideoPip({
                anchor: root,
                video: mediaItems[firstVideoIdx],
                goToVideo: () => { showIndex(firstVideoIdx); restartInterval(); },
                getGalleryVideo: () => (activeVideo && stage.contains(activeVideo) ? activeVideo : null),
                pauseGalleryVideo: () => { if (activeVideo) { try { activeVideo.pause(); } catch { } } },
                closeLabel: window.trans('Close video'),
            });
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
