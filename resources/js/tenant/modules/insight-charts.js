import Chart from 'chart.js/auto';
import { on } from '../core/events.js';

const chartCanvasIds = ['revenueChart', 'donutChart', 'barChart', 'lineChart', 'radarChart'];
let charts = {};

const byId = (id) => document.getElementById(id);
const cssVar = (value) => getComputedStyle(document.documentElement).getPropertyValue(value).trim();

function hasDashboardCanvas() {
    return chartCanvasIds.some((id) => byId(id));
}

function getDashboardCharts() {
    const payload = byId('dashboard-chart-data');

    if (!payload) {
        return {};
    }

    try {
        return JSON.parse(payload.textContent || '{}');
    } catch {
        return {};
    }
}

function tooltipOptions() {
    return {
        backgroundColor: cssVar('--card'),
        borderColor: cssVar('--border2'),
        borderWidth: 1,
        padding: 10,
        titleFont: { family: 'Syne', weight: '700', size: 11 },
        bodyFont: { family: 'DM Sans', size: 11.5 },
        titleColor: cssVar('--t1'),
        bodyColor: cssVar('--t2'),
    };
}

function gridColor() {
    return cssVar('--border');
}

function chartColor(value, index = 0) {
    const fallbackPalette = [cssVar('--cyan'), cssVar('--violet'), cssVar('--green'), cssVar('--amber')];
    const palette = {
        cyan: cssVar('--cyan'),
        violet: cssVar('--violet'),
        green: cssVar('--green'),
        amber: cssVar('--amber'),
        red: cssVar('--red'),
    };

    return palette[value] || value || fallbackPalette[index % fallbackPalette.length];
}

function formatChartValue(value, format = 'number') {
    const numericValue = Number(value || 0);

    switch (format) {
        case 'currency':
            return `$${numericValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        case 'percent':
            return `${numericValue.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}%`;
        default:
            return numericValue.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }
}

function buildLineDatasets(definitions, context, defaultFill = false) {
    return (definitions || []).map((definition, index) => {
        const color = chartColor(definition.color, index);
        const gradient = context.createLinearGradient(0, 0, 0, 240);
        gradient.addColorStop(0, `${color}44`);
        gradient.addColorStop(1, `${color}00`);

        return {
            label: definition.label || `Series ${index + 1}`,
            data: definition.data || [],
            borderColor: color,
            backgroundColor: (definition.fill ?? defaultFill) ? gradient : 'transparent',
            fill: definition.fill ?? defaultFill,
            tension: definition.tension ?? 0.43,
            pointBackgroundColor: color,
            pointRadius: definition.pointRadius ?? 3,
            pointHoverRadius: definition.pointHoverRadius ?? 5,
            borderWidth: definition.borderWidth ?? 2.4,
            borderDash: definition.dashed ? [5, 3] : definition.borderDash || [],
            format: definition.format || 'number',
        };
    });
}

function buildBarDatasets(definitions) {
    return (definitions || []).map((definition, index) => {
        const color = chartColor(definition.color, index);

        return {
            label: definition.label || `Series ${index + 1}`,
            data: definition.data || [],
            backgroundColor(context) {
                const gradient = context.chart.ctx.createLinearGradient(0, 0, 0, 180);
                gradient.addColorStop(0, `${color}cc`);
                gradient.addColorStop(1, `${color}28`);
                return gradient;
            },
            borderRadius: definition.borderRadius ?? 5,
            borderSkipped: false,
            format: definition.format || 'number',
        };
    });
}

function buildRadarDatasets(definitions) {
    return (definitions || []).map((definition, index) => {
        const color = chartColor(definition.color, index);

        return {
            label: definition.label || `Series ${index + 1}`,
            data: definition.data || [],
            borderColor: color,
            backgroundColor: `${color}${definition.alpha || '1a'}`,
            pointBackgroundColor: color,
            pointRadius: definition.pointRadius ?? 3,
            borderWidth: definition.borderWidth ?? 2,
            format: definition.format || 'number',
        };
    });
}

function buildCharts() {
    const revenueCanvas = byId('revenueChart');
    const donutCanvas = byId('donutChart');
    const barCanvas = byId('barChart');
    const lineCanvas = byId('lineChart');
    const radarCanvas = byId('radarChart');

    if (![revenueCanvas, donutCanvas, barCanvas, lineCanvas, radarCanvas].some(Boolean)) {
        return;
    }

    Chart.defaults.color = cssVar('--t2');
    Chart.defaults.font.family = 'DM Sans';
    Chart.defaults.font.size = 11;

    const cyan = cssVar('--cyan');
    const violet = cssVar('--violet');
    const green = cssVar('--green');
    const amber = cssVar('--amber');
    const dashboardCharts = getDashboardCharts();

    const revenueLabels = dashboardCharts.revenueLabels ?? [];
    const donutLabels = dashboardCharts.donutLabels ?? [];
    const donutData = dashboardCharts.donutData ?? [];
    const revenueDatasets = dashboardCharts.revenueDatasets ?? [];
    const barLabels = dashboardCharts.barLabels ?? revenueLabels;
    const barDatasets = dashboardCharts.barDatasets ?? [];
    const lineLabels = dashboardCharts.lineLabels ?? [];
    const lineDatasets = dashboardCharts.lineDatasets ?? [];
    const radarLabels = dashboardCharts.radarLabels ?? [];
    const radarDatasets = dashboardCharts.radarDatasets ?? [];

    if (revenueCanvas) {
        const revenueContext = revenueCanvas.getContext('2d');

        charts.revenue = new Chart(revenueContext, {
            type: 'line',
            data: { labels: revenueLabels, datasets: buildLineDatasets(revenueDatasets, revenueContext, true) },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', align: 'end', labels: { boxWidth: 8, padding: 14, usePointStyle: true, color: cssVar('--t2') } },
                    tooltip: { ...tooltipOptions(), callbacks: { label: (c) => `${c.dataset.label}: ${formatChartValue(c.parsed.y, c.dataset.format)}` } },
                },
                scales: {
                    x: { grid: { color: gridColor() }, border: { display: false }, ticks: { color: cssVar('--t2') } },
                    y: { grid: { color: gridColor() }, border: { display: false }, ticks: { color: cssVar('--t2'), callback: (v) => formatChartValue(v, revenueDatasets[0]?.format || 'number') } },
                },
            },
        });
    }

    if (donutCanvas) {
        charts.donut = new Chart(donutCanvas.getContext('2d'), {
            type: 'doughnut',
            data: { labels: donutLabels, datasets: [{ data: donutData, backgroundColor: [cyan, violet, green, amber], borderWidth: 0, hoverOffset: 5 }] },
            options: {
                responsive: true,
                cutout: '74%',
                plugins: {
                    legend: { display: false },
                    tooltip: { ...tooltipOptions(), callbacks: { label: (c) => `${c.label}: ${formatChartValue(c.parsed, dashboardCharts.donutFormat || 'number')}` } },
                },
            },
        });
    }

    if (barCanvas) {
        charts.bar = new Chart(barCanvas.getContext('2d'), {
            type: 'bar',
            data: { labels: barLabels, datasets: buildBarDatasets(barDatasets) },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: barDatasets.length > 1 },
                    tooltip: { ...tooltipOptions(), callbacks: { label: (c) => `${c.dataset.label}: ${formatChartValue(c.parsed.y, c.dataset.format)}` } },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: {
                            color: cssVar('--t2'),
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 0,
                            callback: (value) => {
                                const label = barLabels[value] ?? '';
                                return label.length > 18 ? `${label.slice(0, 17)}…` : label;
                            },
                        },
                    },
                    y: { grid: { color: gridColor() }, border: { display: false }, ticks: { color: cssVar('--t2'), callback: (v) => formatChartValue(v, barDatasets[0]?.format || 'number') } },
                },
            },
        });
    }

    if (lineCanvas) {
        const lineContext = lineCanvas.getContext('2d');

        charts.line = new Chart(lineContext, {
            type: 'line',
            data: { labels: lineLabels, datasets: buildLineDatasets(lineDatasets, lineContext, false) },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', align: 'end', labels: { boxWidth: 8, padding: 12, usePointStyle: true, color: cssVar('--t2') } },
                    tooltip: { ...tooltipOptions(), callbacks: { label: (c) => `${c.dataset.label}: ${formatChartValue(c.parsed.y, c.dataset.format)}` } },
                },
                scales: {
                    x: { grid: { display: false }, border: { display: false }, ticks: { color: cssVar('--t2') } },
                    y: { grid: { color: gridColor() }, border: { display: false }, ticks: { color: cssVar('--t2'), callback: (v) => formatChartValue(v, lineDatasets[0]?.format || 'number') } },
                },
            },
        });
    }

    if (radarCanvas) {
        charts.radar = new Chart(radarCanvas.getContext('2d'), {
            type: 'radar',
            data: { labels: radarLabels, datasets: buildRadarDatasets(radarDatasets) },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top', align: 'end', labels: { boxWidth: 8, padding: 12, usePointStyle: true, color: cssVar('--t2') } },
                    tooltip: { ...tooltipOptions(), callbacks: { label: (c) => `${c.dataset.label}: ${formatChartValue(c.parsed.r, c.dataset.format)}` } },
                },
                scales: {
                    r: {
                        grid: { color: gridColor() },
                        angleLines: { color: gridColor() },
                        pointLabels: { font: { size: 9.5, family: 'Syne' }, color: cssVar('--t2') },
                        ticks: { display: false },
                        suggestedMin: 0,
                        suggestedMax: 100,
                    },
                },
            },
        });
    }
}

function destroyCharts() {
    Object.values(charts).forEach((chart) => chart.destroy());
    charts = {};
}

function rebuildCharts() {
    if (!hasDashboardCanvas()) {
        return;
    }

    destroyCharts();
    window.requestAnimationFrame(buildCharts);
}

function setPeriod(target) {
    document.querySelectorAll('#periodBtns .pb').forEach((button) => button.classList.remove('p-act'));
    target.classList.add('p-act');
}

document.addEventListener('click', (event) => {
    const target = event.target.closest('[data-action="set-period"]');
    if (target) {
        setPeriod(target);
    }
});

export function mountInsightCharts() {
    if (!hasDashboardCanvas()) {
        return;
    }

    rebuildCharts();

    on('tenant:theme-changed', () => rebuildCharts());
}
