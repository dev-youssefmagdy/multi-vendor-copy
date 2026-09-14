import { on } from '../core/events.js';

function activate(el, code) {
    el.querySelectorAll('[data-locale-tab]').forEach((tab) => {
        const active = tab.dataset.localeTab === code;
        tab.classList.toggle('is-active', active);
        tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    el.querySelectorAll('[data-locale-pane]').forEach((pane) => {
        const active = pane.dataset.localePane === code;
        pane.classList.toggle('is-active', active);
        pane.hidden = !active;
    });
}

function refreshErrorDots(el) {
    el.querySelectorAll('[data-locale-pane]').forEach((pane) => {
        const hasError = pane.querySelector('.is-invalid') !== null;
        const tab = el.querySelector(`[data-locale-tab="${CSS.escape(pane.dataset.localePane)}"]`);
        const dot = tab?.querySelector('.t-locale-error-dot');
        if (dot) {
            dot.hidden = !hasError;
        }
    });
}

export function init(el) {
    el.querySelectorAll('[data-locale-tab]').forEach((tab) => {
        tab.addEventListener('click', () => activate(el, tab.dataset.localeTab));
    });

    const observer = new MutationObserver(() => refreshErrorDots(el));
    el.querySelectorAll('[data-locale-pane]').forEach((pane) => {
        observer.observe(pane, { attributes: true, attributeFilter: ['class'], subtree: true });
    });

    on('tenant:reveal', ({ target }) => {
        const pane = target?.closest('[data-locale-pane]');
        if (pane && el.contains(pane)) {
            activate(el, pane.dataset.localePane);
        }
    });
}
