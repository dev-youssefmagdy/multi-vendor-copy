import '@tenant-css/pages/dashboard.css';
import { mountInsightCharts } from '@tenant/modules/insight-charts.js';
import { mountPerformanceCharts } from './performance.js';

mountInsightCharts();
mountPerformanceCharts();
