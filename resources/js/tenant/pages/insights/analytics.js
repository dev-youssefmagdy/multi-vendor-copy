// Single Analytics page: design-system charts for the active tab and the
// "download report" CSV (KPI cards + every table on the tab, all rows).
import '@tenant-css/pages/analytics.css';
import Chart from 'chart.js/auto';
import { on } from '@tenant/core/events.js';
import { toast } from '@tenant/core/toast.js';

const FONT = 'Outfit, ui-sans-serif, system-ui, sans-serif';

// Series colours by position, per chart (matches the design)
const PALETTE = {
    revenueChart: ['#3B82F6', '#10B981'],
    barChart: ['#FD6F04', '#10B981', '#3B82F6', '#A78BFA'],
    lineChart: ['#A78BFA', '#3B82F6', '#10B981', '#FD6F04'],
};

let charts = [];

const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';
const tickColor = () => (isDark() ? '#787878' : '#9CA3AF');
const gridColor = () => (isDark() ? '#2a2a2d' : '#F3F4F6');

function payload() {
    try {
        return JSON.parse(document.getElementById('dashboard-chart-data')?.textContent || '{}');
    } catch {
        return {};
    }
}

function formatValue(value, format) {
    const n = Number(value) || 0;
    if (format === 'currency') return `$${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    if (format === 'percent') return `${n.toLocaleString('en-US', { maximumFractionDigits: 2 })}%`;
    return n.toLocaleString('en-US', { maximumFractionDigits: 2 });
}

function compactTick(value, format) {
    const n = Number(value) || 0;
    const prefix = format === 'currency' ? '$' : '';
    if (Math.abs(n) >= 1000) return `${prefix}${(n / 1000).toFixed(1).replace(/\.0$/, '')}k`;
    return `${prefix}${n.toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
}

function tooltip() {
    return {
        backgroundColor: isDark() ? '#232326' : '#FFFFFF',
        borderColor: isDark() ? '#3a3a3d' : '#E5E7EB',
        borderWidth: 1,
        padding: 10,
        titleColor: isDark() ? '#F5F5F5' : '#111111',
        bodyColor: isDark() ? '#C1C1C1' : '#6B7280',
        titleFont: { family: FONT, weight: '600', size: 12 },
        bodyFont: { family: FONT, size: 12 },
        usePointStyle: true,
        boxWidth: 8,
        boxHeight: 8,
        callbacks: {
            label: (c) => ` ${c.dataset.label}: ${formatValue(c.parsed.y ?? c.parsed, c.dataset.format)}`,
        },
    };
}

function renderLegend(canvasId, datasets) {
    const el = document.querySelector(`[data-legend-for="${canvasId}"]`);
    if (!el) return;
    el.innerHTML = '';
    datasets.forEach((d) => {
        const item = document.createElement('span');
        const dot = document.createElement('i');
        dot.style.background = d.borderColor || d.backgroundColor;
        item.append(dot, document.createTextNode(d.label));
        el.append(item);
    });
}

function scales(format, { gridX = false } = {}) {
    return {
        x: { grid: { display: gridX }, border: { display: false }, ticks: { color: tickColor(), font: { family: FONT, size: 12 }, maxRotation: 0, autoSkip: true } },
        y: {
            beginAtZero: true,
            border: { display: false },
            grid: { color: (ctx) => (ctx.tick.value === 0 ? (isDark() ? '#3a3a3d' : '#E5E7EB') : gridColor()) },
            ticks: { color: tickColor(), maxTicksLimit: 5, font: { family: FONT, size: 12 }, callback: (v) => compactTick(v, format) },
        },
    };
}

function lineChart(id, labels, definitions) {
    const canvas = document.getElementById(id);
    if (!canvas || !definitions?.length) return;

    const colors = PALETTE[id] || PALETTE.lineChart;
    const datasets = definitions.map((d, i) => {
        const color = colors[i % colors.length];
        return {
            label: d.label,
            data: d.data || [],
            format: d.format || 'number',
            borderColor: color,
            backgroundColor: `${color}14`,
            fill: 'origin',
            tension: 0,
            borderWidth: 1.86,
            pointRadius: 3.5,
            pointHoverRadius: 5,
            pointBackgroundColor: '#FFFFFF',
            pointBorderColor: color,
            pointBorderWidth: 1.5,
        };
    });

    renderLegend(id, datasets);
    charts.push(new Chart(canvas, {
        type: 'line',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false }, tooltip: tooltip() },
            scales: scales(datasets[0].format),
        },
    }));
}

function barChart(id, labels, definitions) {
    const canvas = document.getElementById(id);
    if (!canvas || !definitions?.length) return;

    const colors = PALETTE.barChart;
    const datasets = definitions.map((d, i) => ({
        label: d.label,
        data: d.data || [],
        format: d.format || 'number',
        backgroundColor: colors[i % colors.length],
        borderRadius: { topLeft: 8, topRight: 8 },
        borderSkipped: 'bottom',
        maxBarThickness: 15,
        categoryPercentage: 0.6,
        barPercentage: 1,
    }));

    renderLegend(id, datasets);
    charts.push(new Chart(canvas, {
        type: 'bar',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: tooltip() },
            scales: {
                ...scales(datasets[0].format),
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        color: tickColor(),
                        font: { family: FONT, size: 12 },
                        maxRotation: 0,
                        autoSkip: true,
                        callback(value) {
                            const label = String(this.getLabelForValue(value) ?? '');
                            return label.length > 12 ? `${label.slice(0, 11)}…` : label;
                        },
                    },
                },
            },
        },
    }));
}

function donutChart(data) {
    const canvas = document.getElementById('donutChart');
    if (!canvas) return;

    const values = data.donutData || [];
    const hasData = values.some((v) => Number(v) > 0);

    charts.push(new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: hasData ? data.donutLabels || [] : ['No data'],
            datasets: [{
                data: hasData ? values : [1],
                backgroundColor: hasData ? JSON.parse(canvas.dataset.colors || '[]') : [isDark() ? '#2a2a2d' : '#F3F4F6'],
                borderWidth: 0,
                hoverOffset: hasData ? 4 : 0,
                format: data.donutFormat || 'number',
            }],
        },
        options: {
            responsive: false,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: hasData ? { ...tooltip(), callbacks: { label: (c) => ` ${c.label}: ${formatValue(c.parsed, c.dataset.format)}` } } : { enabled: false },
            },
        },
    }));
}

function build() {
    charts.forEach((c) => c.destroy());
    charts = [];

    const data = payload();
    lineChart('revenueChart', data.revenueLabels || [], data.revenueDatasets);
    barChart('barChart', data.barLabels || [], data.barDatasets);
    lineChart('lineChart', data.lineLabels || [], data.lineDatasets);
    donutChart(data);
}

/* ── Download report (CSV) ─────────────────────────────────────────────── */

const csvCell = (value) => {
    const text = String(value ?? '').replace(/\s+/g, ' ').trim();
    return /[",\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
};

const stripHtml = (html) => {
    const div = document.createElement('div');
    div.innerHTML = String(html ?? '');
    return div.textContent || '';
};

async function fetchAllRows(url, columns) {
    const params = new URLSearchParams({ draw: '1', start: '0', length: '-1' });
    columns.forEach((c, i) => params.append(`columns[${i}][data]`, c.data));
    const response = await fetch(`${url}?${params}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return (await response.json()).data || [];
}

async function downloadReport(page, button) {
    button.classList.add('is-loading');
    try {
        const lines = [];
        const heading = page.querySelector('.an-module-head h2')?.textContent.trim() || 'Analytics';
        lines.push(csvCell(heading), '');

        const kpis = [...page.querySelectorAll('.an-kpi')];
        if (kpis.length) {
            lines.push('Metric,Value');
            kpis.forEach((kpi) => lines.push([kpi.querySelector('[data-kpi-label]')?.textContent, kpi.querySelector('[data-kpi-value]')?.textContent].map(csvCell).join(',')));
            lines.push('');
        }

        // Server-rendered tables (e.g. status breakdown)
        page.querySelectorAll('[data-report-table]').forEach((table) => {
            lines.push(csvCell(table.dataset.reportTable));
            table.querySelectorAll('tr').forEach((tr) => {
                const cells = [...tr.children].map((td) => csvCell(td.textContent));
                if (cells.some(Boolean)) lines.push(cells.join(','));
            });
            lines.push('');
        });

        // Ajax data tables — fetch every row, not just the visible page
        for (const source of page.querySelectorAll('[data-report-source]')) {
            const columns = JSON.parse(source.dataset.reportColumns || '[]');
            const rows = await fetchAllRows(source.dataset.reportUrl, columns);
            lines.push(csvCell(source.dataset.reportTitle));
            lines.push(columns.map((c) => csvCell(c.title)).join(','));
            rows.forEach((row) => lines.push(columns.map((c) => csvCell(stripHtml(row[c.data]))).join(',')));
            lines.push('');
        }

        const blob = new Blob([`﻿${lines.join('\n')}`], { type: 'text/csv;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `${page.dataset.reportName || 'analytics'}-${new Date().toISOString().slice(0, 10)}.csv`;
        document.body.append(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(link.href), 1000);
    } catch {
        toast.error('Could not build the report. Please try again.');
    } finally {
        button.classList.remove('is-loading');
    }
}

function init() {
    const page = document.querySelector('[data-analytics-page]');
    if (!page) return;

    build();
    on('tenant:theme-changed', () => window.requestAnimationFrame(build));

    const download = page.querySelector('[data-analytics-download]');
    download?.addEventListener('click', () => downloadReport(page, download));
}

init();
