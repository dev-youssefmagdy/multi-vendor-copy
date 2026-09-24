import '@tenant-css/pages/dashboard.css';
import { mountPerformanceCharts } from './performance.js';
import { mountOpportunities } from './opportunities.js';
import { mountAds } from './ads.js';
import { mountBrandOptions } from './brand.js';
import { mountStorePreview } from './storefront.js';
import { mountPartnerInvite } from './partner.js';

mountPerformanceCharts();
mountOpportunities();
mountAds();
mountBrandOptions();
mountStorePreview();
mountPartnerInvite();
