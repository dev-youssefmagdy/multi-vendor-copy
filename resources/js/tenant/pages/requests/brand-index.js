// Brand Requests list page — all behaviour is declarative via x-tenant:: components.

// Request list design (shared with Orders / Customers).
import '@tenant-css/pages/dashboard.css';
import '@tenant-css/pages/analytics.css';
import '@tenant-css/pages/orders.css';

import { mountMobileKpiCarousel } from '../../core/mobile-kpi-carousel.js';

mountMobileKpiCarousel(document.querySelector('.od-kpis'));
