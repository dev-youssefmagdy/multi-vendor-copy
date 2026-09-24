// Dashboard "winning opportunities": the product-card slideshow and the
// auto-scrolling "best markets" flag ticker inside each card.
import Swiper from 'swiper';
import { A11y, Autoplay, Keyboard, Mousewheel, Navigation } from 'swiper/modules';
import 'swiper/css';

const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function mountFlagTickers(root) {
    root.querySelectorAll('[data-flag-ticker]').forEach((el) => {
        new Swiper(el, {
            modules: [Autoplay],
            nested: true,
            slidesPerView: 'auto',
            spaceBetween: 15.6,
            loop: true,
            speed: 3500,
            allowTouchMove: false,
            autoplay: reducedMotion() ? false : { delay: 0, disableOnInteraction: false, pauseOnMouseEnter: true },
        });
    });
}

function mountSlider(el) {
    const section = el.closest('section');

    new Swiper(el, {
        modules: [A11y, Autoplay, Keyboard, Mousewheel, Navigation],
        slidesPerView: 'auto',
        spaceBetween: 24,
        grabCursor: true,
        rewind: true,
        speed: 600,
        keyboard: { enabled: true, onlyInViewport: true },
        mousewheel: { forceToAxis: true },
        navigation: {
            prevEl: section?.querySelector('[data-opp-prev]'),
            nextEl: section?.querySelector('[data-opp-next]'),
        },
        autoplay: reducedMotion() ? false : { delay: 5000, disableOnInteraction: false, pauseOnMouseEnter: true },
        a11y: { prevSlideMessage: 'Previous opportunity', nextSlideMessage: 'Next opportunity' },
        // Clicking a card's buttons must not be swallowed as a drag.
        noSwipingSelector: 'button, a',
    });
}

export function mountOpportunities(root = document) {
    const slider = root.querySelector('[data-opp-slider]');
    if (!slider) return;

    // Tickers first so each card's inner swiper exists before the outer one measures the slides.
    mountFlagTickers(slider);
    mountSlider(slider);
}
