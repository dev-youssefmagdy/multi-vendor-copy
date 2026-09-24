import '@tenant-css/pages/dashboard.css';
import { mountInsightCharts } from '@tenant/modules/insight-charts.js';
import { mountPerformanceCharts } from './performance.js';
import { mountOpportunities } from './opportunities.js';
import { mountAds } from './ads.js';
import { mountBrandOptions } from './brand.js';
import { mountStorePreview } from './storefront.js';

mountInsightCharts();
mountPerformanceCharts();
mountOpportunities();
mountAds();
mountBrandOptions();
mountStorePreview();
