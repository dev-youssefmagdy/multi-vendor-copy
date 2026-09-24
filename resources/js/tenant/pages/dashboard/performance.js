// Dashboard "Your store's performance" charts: Gross vs Collected (line)
// and Status Mix (donut). Styled to the vendor design system; the shared
// insight-charts module still draws the remaining legacy dashboard charts.
import Chart from 'chart.js/auto';
import { on } from '@tenant/core/events.js';

const GROSS = '#3B82F6';
const COLLECTED = '#10B981';
const FONT = 'Outfit, ui-sans-serif, system-ui, sans-serif';

let charts = [];

const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';

function payload() {
    try {
        return JSON.parse(document.getElementById('dashboard-chart-data')?.textContent || '{}');
    } catch {
        return {};
    }
}

function compactCurrency(value) {
    const n = Number(value) || 0;
    if (Math.abs(n) >= 1000) {
        return `$${(n / 1000).toFixed(1).replace(/\.0$/, '')}k`;
    }
    return `$${n.toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
}

function fullCurrency(value) {
    return `$${(Number(value) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
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
    };
}

function lineDataset(label, data, color) {
    return {
        label,
        data,
        borderColor: color,
        backgroundColor: `${color}12`,
        fill: 'origin',
        tension: 0,
        borderWidth: 1.86,
        pointRadius: 3.5,
        pointHoverRadius: 5,
        pointBackgroundColor: '#FFFFFF',
        pointBorderColor: color,
        pointBorderWidth: 1.5,
    };
}

function buildGross(data) {
    const canvas = document.getElementById('dbGrossChart');
    if (!canvas) return;

    const datasets = data.revenueDatasets ?? [];
    const gross = datasets.find((d) => /gross/i.test(d.label))?.data ?? datasets[0]?.data ?? [];
    const collected = datasets.find((d) => /collect/i.test(d.label))?.data ?? datasets[1]?.data ?? [];
    const tick = isDark() ? '#787878' : '#9CA3AF';

    charts.push(new Chart(canvas, {
        type: 'line',
        data: {
            labels: data.revenueLabels ?? [],
            datasets: [lineDataset('Gross sales', gross, GROSS), lineDataset('Collected', collected, COLLECTED)],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: { ...tooltip(), callbacks: { label: (c) => ` ${c.dataset.label}: ${fullCurrency(c.parsed.y)}` } },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: tick, font: { family: FONT, size: 12 } },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: (ctx) => (ctx.tick.value === 0 ? (isDark() ? '#3a3a3d' : '#E5E7EB') : (isDark() ? '#2a2a2d' : '#F3F4F6')) },
                    border: { display: false },
                    ticks: { color: tick, maxTicksLimit: 5, font: { family: FONT, size: 12 }, callback: compactCurrency },
                },
            },
        },
    }));
}

function buildStatus() {
    const canvas = document.getElementById('dbStatusChart');
    if (!canvas) return;

    const values = JSON.parse(canvas.dataset.values || '[]');
    const hasData = values.some((v) => v > 0);

    charts.push(new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: hasData ? JSON.parse(canvas.dataset.labels || '[]') : ['No orders'],
            datasets: [{
                data: hasData ? values : [1],
                backgroundColor: hasData ? JSON.parse(canvas.dataset.colors || '[]') : [isDark() ? '#2a2a2d' : '#F3F4F6'],
                borderWidth: 0,
                hoverOffset: hasData ? 4 : 0,
            }],
        },
        options: {
            responsive: false,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: hasData ? tooltip() : { enabled: false },
            },
        },
    }));
}

function build() {
    charts.forEach((c) => c.destroy());
    charts = [];
    const data = payload();
    buildGross(data);
    buildStatus();
}

export function mountPerformanceCharts() {
    if (!document.getElementById('dbGrossChart') && !document.getElementById('dbStatusChart')) {
        return;
    }

    build();
    on('tenant:theme-changed', () => window.requestAnimationFrame(build));
}
