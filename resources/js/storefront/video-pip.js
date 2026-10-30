// Floating product-video popup shared by every storefront theme.
//
// Behaviour (theme-agnostic):
//  - Gallery in view            -> popup hidden, the video lives in the gallery slider.
//  - Gallery scrolled out of view -> popup shown (continues from the gallery video's time).
//  - Gallery scrolled back into view, or the popup is clicked
//                               -> popup hidden, gallery jumps to the video slide and keeps
//                                  playing from the popup's time. A click also scrolls to the gallery.
//  - Close button               -> popup stays hidden for the rest of the page view.
//
// Themes only supply adapters for their own gallery (Swiper / plain JS / custom stage).

let current = null;

const DEFAULT_BOX = { top: 'auto', bottom: '90px', right: '12px', width: '140px', height: '190px' };

export function mountVideoPip(options) {
    if (current) current.destroy();

    const {
        anchor, // element whose visibility drives the popup (the gallery)
        video, // { src, poster }
        goToVideo, // () => void: select the video slide in the gallery
        getGalleryVideo, // () => HTMLVideoElement|null: the gallery's (now active) video element
        pauseGalleryVideo = () => {},
        closeLabel = 'Close video',
        box = {},
    } = options;

    if (!anchor || !video || !video.src || !('IntersectionObserver' in window)) return null;

    const size = { ...DEFAULT_BOX, ...box };
    let pip = null;
    let pipVideo = null;
    let dismissed = false;
    let galleryVisible = true;
    let destroyed = false;

    function build() {
        const wrap = document.createElement('div');
        wrap.dataset.storefrontVideoPip = '';
        wrap.style.cssText = [
            'position:fixed',
            `top:${size.top}`,
            `bottom:${size.bottom}`,
            `right:${size.right}`,
            `width:${size.width}`,
            `height:${size.height}`,
            'border-radius:16px',
            'overflow:hidden',
            'background:#111',
            'box-shadow:0 8px 32px rgba(0,0,0,0.45)',
            'border:1.5px solid rgba(255,255,255,0.18)',
            'z-index:9999',
            'cursor:pointer',
            'transform:translateY(20px)',
            'opacity:0',
            'transition:opacity 0.3s ease,transform 0.3s ease',
        ].join(';');
        wrap.setAttribute('role', 'button');

        const vid = document.createElement('video');
        vid.src = video.src;
        if (video.poster) vid.poster = video.poster;
        vid.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block;pointer-events:none;';
        vid.autoplay = true;
        vid.muted = true;
        vid.loop = true;
        vid.playsInline = true;
        vid.setAttribute('playsinline', '');

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.innerHTML = '&times;';
        closeBtn.setAttribute('aria-label', closeLabel);
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
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dismissed = true;
            hide();
        });

        wrap.addEventListener('click', () => restore(true));

        wrap.appendChild(vid);
        wrap.appendChild(closeBtn);
        return { wrap, vid };
    }

    function show() {
        if (pip || dismissed || destroyed) return;

        const galleryVid = getGalleryVideo();
        const startAt = galleryVid ? galleryVid.currentTime : 0;
        pauseGalleryVideo();

        const built = build();
        pip = built.wrap;
        pipVideo = built.vid;
        document.body.appendChild(pip);

        if (startAt > 0) {
            try {
                pipVideo.currentTime = startAt;
            } catch {
                // metadata not loaded yet; start from the beginning
            }
        }

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                if (!pip) return;
                pip.style.opacity = '1';
                pip.style.transform = 'translateY(0)';
            });
        });
        pipVideo.play().catch(() => {});
    }

    function hide() {
        if (!pip) return null;
        const time = pipVideo ? pipVideo.currentTime : 0;
        try {
            pipVideo.pause();
        } catch {
            // ignore
        }
        const node = pip;
        pip = null;
        pipVideo = null;
        node.style.opacity = '0';
        node.style.transform = 'translateY(20px)';
        setTimeout(() => node.parentNode && node.parentNode.removeChild(node), 300);
        return time;
    }

    // Hand playback back to the gallery: select the video slide and continue from the popup's time.
    function restore(scrollToGallery) {
        const time = hide();
        if (time === null) return;

        goToVideo();
        requestAnimationFrame(() => {
            const galleryVid = getGalleryVideo();
            if (galleryVid) {
                try {
                    galleryVid.currentTime = time;
                } catch {
                    // ignore
                }
                galleryVid.play().catch(() => {});
            }
        });

        if (scrollToGallery) {
            anchor.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    const observer = new IntersectionObserver((entries) => {
        const entry = entries[entries.length - 1];
        galleryVisible = entry.isIntersecting;
        if (galleryVisible) {
            restore(false);
        } else {
            show();
        }
    });
    observer.observe(anchor);

    const instance = {
        destroy() {
            destroyed = true;
            observer.disconnect();
            if (pip && pip.parentNode) pip.parentNode.removeChild(pip);
            pip = null;
            pipVideo = null;
            if (current === instance) current = null;
        },
    };
    current = instance;
    return instance;
}

document.addEventListener('livewire:navigate', () => {
    if (current) current.destroy();
});

window.StorefrontVideoPip = { mount: mountVideoPip };
