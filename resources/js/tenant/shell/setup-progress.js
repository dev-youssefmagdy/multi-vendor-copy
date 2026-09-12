import { get } from '../core/http.js';
import { on } from '../core/events.js';

function applyProgress(el, progress) {
    const percentEl = el.querySelector('[data-setup-percent]');
    const bar = el.querySelector('[data-setup-bar]');

    if (percentEl) {
        percentEl.textContent = progress.percent;
    }

    if (bar) {
        bar.style.setProperty('--p', `${progress.percent}%`);
        bar.classList.toggle('is-done', progress.percent >= 100);
    }

    progress.steps.forEach((step) => {
        const stepEl = el.querySelector(`[data-step-key="${CSS.escape(step.key)}"]`);
        if (!stepEl) {
            return;
        }
        stepEl.classList.toggle('is-pending', !step.done);
        const row = stepEl.querySelector('.t-setup-progress-step-row');
        row?.classList.toggle('is-done', step.done);
    });
}

async function refresh(el) {
    try {
        const data = await get(el.dataset.url, {}, { toast: false });
        applyProgress(el, data);
    } catch {
        /* keep the last known state on failure */
    }
}

export function init(el) {
    const toggle = el.querySelector('[data-setup-toggle]');
    const steps = el.querySelector('[data-setup-steps]');

    const expanded = localStorage.getItem('tenant-setup-expanded') === '1';
    if (steps) {
        steps.hidden = !expanded;
    }
    el.classList.toggle('is-expanded', expanded);

    toggle?.addEventListener('click', () => {
        const willExpand = steps?.hidden ?? false;
        if (steps) {
            steps.hidden = !willExpand;
        }
        el.classList.toggle('is-expanded', willExpand);
        localStorage.setItem('tenant-setup-expanded', willExpand ? '1' : '0');
    });

    const interval = parseInt(el.dataset.poll || '15000', 10);
    setInterval(() => {
        if (document.visibilityState === 'visible') {
            refresh(el);
        }
    }, interval);

    on('tenant:setup-progress:refresh', () => refresh(el));
}
