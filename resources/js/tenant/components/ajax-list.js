import { get } from '../core/http.js';
import { on } from '../core/events.js';
import { initComponents } from '../core/registry.js';

async function load(el, url) {
    try {
        const response = await get(url, {}, { toast: false });
        const body = el.querySelector('[data-ajax-list-body]');
        const pagination = el.querySelector('[data-ajax-list-pagination]');

        if (body) {
            body.innerHTML = response.html || `<div class="empty-state"><div class="empty-state-title">${el.dataset.empty}</div></div>`;
            initComponents(body);
        }
        if (pagination) {
            pagination.innerHTML = response.pagination || '';
        }

        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch {
        /* handled by http interceptor */
    }
}

export function init(el) {
    const baseUrl = el.dataset.url;

    el.addEventListener('click', (event) => {
        const link = event.target.closest('[data-ajax-page]');
        if (link) {
            event.preventDefault();
            load(el, link.href);
            history.replaceState(history.state, '', link.href);
        }
    });

    on('tenant:table:reload', ({ selector }) => {
        if (selector === `#${el.id}` || selector === el.id) {
            load(el, baseUrl);
        }
    });
}
