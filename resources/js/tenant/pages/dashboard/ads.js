// Dashboard "Successful advertisements": plays an ad's video inside its card.
// While a video plays, the card slideshow's autoplay is paused so the slide
// doesn't move away mid-watch; it resumes when the video ends.

function sliderOf(el) {
    return el.closest('[data-opp-slider]')?.swiper;
}

function stopOthers(current) {
    document.querySelectorAll('.db-ad-media.is-playing video').forEach((video) => {
        if (video !== current) video.pause();
    });
}

function play(button) {
    const media = button.closest('[data-ad-media]');
    const src = button.dataset.video;
    if (!media || !src) return;

    let video = media.querySelector('video');
    if (!video) {
        video = document.createElement('video');
        video.className = 'db-ad-video';
        video.src = src;
        video.controls = true;
        video.playsInline = true;
        video.setAttribute('aria-label', button.getAttribute('aria-label') || 'Ad video');
        video.addEventListener('play', () => {
            stopOthers(video);
            sliderOf(media)?.autoplay?.stop();
        });
        video.addEventListener('ended', () => sliderOf(media)?.autoplay?.start());
        media.appendChild(video);
    }

    media.classList.add('is-playing');
    video.play().catch(() => {});
    video.focus();
}

export function mountAds(root = document) {
    root.addEventListener('click', (event) => {
        const button = event.target.closest('[data-ad-play]');
        if (button) play(button);
    });
}
