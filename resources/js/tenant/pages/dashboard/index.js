import '@tenant-css/pages/dashboard.css';
import { initComponents } from '@tenant/core';
import { mountPerformanceCharts } from './performance.js';
import { mountOpportunities } from './opportunities.js';
import { mountAds } from './ads.js';
import { mountBrandOptions } from './brand.js';
import { mountStorePreview } from './storefront.js';
import { mountPartnerInvite } from './partner.js';
import { mountProductModals } from '../../components/product-modals.js';

mountPerformanceCharts();
mountOpportunities();
mountAds();
mountBrandOptions();
mountStorePreview();
mountPartnerInvite();
initComponents();
mountProductModals();
