// Products module header: the module tabs are a swipeable carousel that
// starts scrolled to the active tab.
import '@tenant-css/pages/products-module.css';
import Swiper from 'swiper';
import { FreeMode, Mousewheel } from 'swiper/modules';
import 'swiper/css';

document.querySelectorAll('[data-pm-tabs]').forEach((el) => {
    const slides = [...el.querySelectorAll('.swiper-slide')];
    const active = Math.max(0, slides.findIndex((s) => s.classList.contains('is-active')));

    const swiper = new Swiper(el, {
        modules: [FreeMode, Mousewheel],
        slidesPerView: 'auto',
        spaceBetween: 12,
        freeMode: { enabled: true, sticky: false },
        mousewheel: { forceToAxis: true },
        grabCursor: true,
        slideToClickedSlide: false,
    });

    // Bring the active tab into view without animating on load.
    if (active > 0) swiper.slideTo(Math.max(0, active - 1), 0);
});
