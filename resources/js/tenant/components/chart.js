import { on } from '../core/events.js';

const instances = new WeakMap();

function cssVar(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
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

function buildChart(el, Chart) {
    const type = el.dataset.type || 'line';
    const configScript = document.getElementById(`${el.id}-config`);
    const config = configScript ? JSON.parse(configScript.textContent || '{}') : {};
    const context = el.getContext('2d');

    Chart.defaults.color = cssVar('--t2');
    Chart.defaults.font.family = 'DM Sans';
    Chart.defaults.font.size = 11;

    let datasets;
    let options;

    if (type === 'bar') {
        datasets = buildBarDatasets(config.datasets);
        options = {
            responsive: true,
            plugins: {
                legend: { display: (config.datasets || []).length > 1 },
                tooltip: { ...tooltipOptions(), callbacks: { label: (c) => `${c.dataset.label}: ${formatChartValue(c.parsed.y, c.dataset.format)}` } },
            },
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { color: cssVar('--t2') } },
                y: { grid: { color: gridColor() }, border: { display: false }, ticks: { color: cssVar('--t2'), callback: (v) => formatChartValue(v, config.datasets?.[0]?.format || 'number') } },
            },
        };
    } else if (type === 'doughnut' || type === 'pie') {
        options = {
            responsive: true,
            cutout: type === 'doughnut' ? '74%' : undefined,
            plugins: {
                legend: { display: false },
                tooltip: { ...tooltipOptions(), callbacks: { label: (c) => `${c.label}: ${formatChartValue(c.parsed, config.format || 'number')}` } },
            },
        };
        datasets = [{
            data: config.data || [],
            backgroundColor: (config.colors || ['cyan', 'violet', 'green', 'amber']).map((c) => chartColor(c)),
            borderWidth: 0,
            hoverOffset: 5,
        }];
    } else if (type === 'radar') {
        datasets = buildRadarDatasets(config.datasets);
        options = {
            responsive: true,
            plugins: { legend: { position: 'top', align: 'end', labels: { boxWidth: 8, padding: 12, usePointStyle: true, color: cssVar('--t2') } } },
            scales: { r: { grid: { color: gridColor() }, angleLines: { color: gridColor() }, pointLabels: { font: { size: 9.5, family: 'Syne' }, color: cssVar('--t2') }, ticks: { display: false }, suggestedMin: 0, suggestedMax: 100 } },
        };
    } else {
        datasets = buildLineDatasets(config.datasets, context, type === 'area');
        options = {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', align: 'end', labels: { boxWidth: 8, padding: 14, usePointStyle: true, color: cssVar('--t2') } },
                tooltip: { ...tooltipOptions(), callbacks: { label: (c) => `${c.dataset.label}: ${formatChartValue(c.parsed.y, c.dataset.format)}` } },
            },
            scales: {
                x: { grid: { color: gridColor() }, border: { display: false }, ticks: { color: cssVar('--t2') } },
                y: { grid: { color: gridColor() }, border: { display: false }, ticks: { color: cssVar('--t2'), callback: (v) => formatChartValue(v, config.datasets?.[0]?.format || 'number') } },
            },
        };
    }

    return new Chart(context, {
        type: type === 'area' ? 'line' : type,
        data: { labels: config.labels || [], datasets },
        options,
    });
}

export async function init(el) {
    const { default: Chart } = await import('chart.js/auto');

    if (!el.id) {
        el.id = `chart-${Math.random().toString(36).slice(2, 10)}`;
    }

    instances.set(el, buildChart(el, Chart));

    on('tenant:theme-changed', () => {
        instances.get(el)?.destroy();
        instances.set(el, buildChart(el, Chart));
    });
}

export function destroy(el) {
    instances.get(el)?.destroy();
    instances.delete(el);
}
