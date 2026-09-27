// x-tenant::stats-grid — KPI cards swipe as a carousel on mobile only.
import { mountMobileKpiCarousel } from '../core/mobile-kpi-carousel.js';

export function init(el) {
    mountMobileKpiCarousel(el);
}
