import { on } from '../core/events.js';

function activate(el, key) {
    el.querySelectorAll('[data-tab-key]').forEach((tab) => {
        tab.classList.toggle('is-active', tab.dataset.tabKey === key);
    });
    el.querySelectorAll('[data-tab-panel]').forEach((panel) => {
        const active = panel.dataset.tabPanel === key;
        panel.classList.toggle('is-active', active);
        panel.hidden = !active;
    });

    if (el.dataset.mode === 'hash') {
        history.replaceState(history.state, '', `#tab-${key}`);
    }
}

export function init(el) {
    const mode = el.dataset.mode;

    el.querySelectorAll('button[data-tab-key]').forEach((tab) => {
        tab.addEventListener('click', () => activate(el, tab.dataset.tabKey));
    });

    if (mode === 'hash' && location.hash.startsWith('#tab-')) {
        activate(el, location.hash.replace('#tab-', ''));
    }

    on('tenant:reveal', ({ target }) => {
        const panel = target?.closest('[data-tab-panel]');
        if (panel && el.contains(panel)) {
            activate(el, panel.dataset.tabPanel);
        }
    });
}
