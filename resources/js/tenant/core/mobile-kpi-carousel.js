// Turns a KPI card grid into a Swiper carousel on mobile only (≤ 767px);
// larger screens keep the grid — Swiper is created/destroyed with the breakpoint.
import Swiper from 'swiper';
import 'swiper/css';

export function mountMobileKpiCarousel(root, { slidesPerView = 1.25, spaceBetween = 12 } = {}) {
    if (!root) return;

    const mobile = window.matchMedia('(max-width: 767px)');
    const cards = [...root.children];
    const wrapper = document.createElement('div');
    let swiper = null;

    const sync = () => {
        if (mobile.matches && !swiper) {
            wrapper.className = 'swiper-wrapper';
            cards.forEach((card) => { card.classList.add('swiper-slide'); wrapper.append(card); });
            root.append(wrapper);
            root.classList.add('swiper', 'is-carousel');
            swiper = new Swiper(root, { slidesPerView, spaceBetween, watchOverflow: true });
        } else if (!mobile.matches && swiper) {
            swiper.destroy(true, true);
            swiper = null;
            cards.forEach((card) => { card.classList.remove('swiper-slide'); root.append(card); });
            wrapper.remove();
            root.classList.remove('swiper', 'is-carousel');
        }
    };

    sync();
    mobile.addEventListener('change', sync);
}
