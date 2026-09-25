// Dashboard product-card slideshows (winning opportunities, new in) and the
// auto-scrolling "best markets" flag ticker inside opportunity cards.
import Swiper from 'swiper';
import { A11y, Autoplay, Keyboard, Mousewheel } from 'swiper/modules';
import 'swiper/css';

const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function mountFlagTickers(root = document) {
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
    // Optional mobile layout: data-mobile-view="1.5" shows 1.5 cards (12px apart)
    // below 768px; larger screens keep the cards' own CSS widths.
    const mobileView = parseFloat(el.dataset.mobileView || '');
    const breakpoints = mobileView
        ? { 0: { slidesPerView: mobileView, spaceBetween: 12 }, 768: { slidesPerView: 'auto', spaceBetween: 24 } }
        : undefined;

    new Swiper(el, {
        modules: [A11y, Autoplay, Keyboard, Mousewheel],
        slidesPerView: 'auto',
        spaceBetween: 24,
        breakpoints,
        grabCursor: true,
        rewind: true,
        speed: 600,
        keyboard: { enabled: true, onlyInViewport: true },
        mousewheel: { forceToAxis: true },
        autoplay: reducedMotion() ? false : { delay: 5000, disableOnInteraction: false, pauseOnMouseEnter: true },
        a11y: { prevSlideMessage: 'Previous products', nextSlideMessage: 'Next products' },
        // Clicking a card's buttons / video controls must not be swallowed as a drag.
        noSwipingSelector: 'button, a, video',
    });
}

// Mounts every product-card slider on the page (winning opportunities, new in, …).
export function mountOpportunities(root = document) {
    root.querySelectorAll('[data-opp-slider]').forEach((slider) => {
        // Tickers first so each card's inner swiper exists before the outer one measures the slides.
        mountFlagTickers(slider);
        mountSlider(slider);
    });
}
