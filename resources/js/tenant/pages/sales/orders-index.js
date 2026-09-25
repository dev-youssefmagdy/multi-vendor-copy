// Orders list page — behaviour is declarative via x-tenant:: components;
// this entry loads the page design and the mobile KPI carousel.
import '@tenant-css/pages/dashboard.css';
import '@tenant-css/pages/analytics.css';
import '@tenant-css/pages/orders.css';

import { mountMobileKpiCarousel } from '../../core/mobile-kpi-carousel.js';

mountMobileKpiCarousel(document.querySelector('.od-kpis'));
