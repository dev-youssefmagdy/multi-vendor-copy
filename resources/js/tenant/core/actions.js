import { request } from './http.js';
import { confirm } from './confirm.js';
import { setBusy } from './dom.js';
import { emit } from './events.js';

function applySuccess(el, response) {
    const behaviours = (el.dataset.success || '').split(/\s+/).filter(Boolean);

    behaviours.forEach((behaviour) => {
        if (behaviour.startsWith('reload-table:')) {
            emit('tenant:table:reload', { selector: behaviour.slice('reload-table:'.length) });
        } else if (behaviour === 'close-modal') {
            const modal = el.closest('[data-tenant-modal]');
            if (modal) {
                import('./modals.js').then(({ closeModal }) => closeModal(modal.id));
            }
        } else if (behaviour === 'reload-page') {
            location.reload();
        } else if (behaviour === 'redirect' && response?.redirect) {
            location.assign(response.redirect);
        } else if (behaviour.startsWith('emit:')) {
            emit(behaviour.slice('emit:'.length), { response });
        }
    });
}

async function handleAction(el) {
    const url = el.dataset.actionUrl;
    const method = (el.dataset.actionMethod || 'POST').toLowerCase();

    if (el.dataset.confirm) {
        const ok = await confirm({
            text: el.dataset.confirm,
            danger: el.dataset.confirmDanger !== undefined,
        });
        if (!ok) {
            return;
        }
    }

    let payload = null;
    if (el.dataset.payload) {
        try {
            payload = JSON.parse(el.dataset.payload);
        } catch {
            payload = null;
        }
    } else if (el.dataset.payloadKey && el instanceof HTMLInputElement) {
        payload = { [el.dataset.payloadKey]: el.checked ? 1 : 0 };
    }

    setBusy(el, true);

    try {
        const response = await request(method, url, payload);
        applySuccess(el, response);
    } catch {
        if (el.type === 'checkbox') {
            el.checked = !el.checked;
        }
    } finally {
        setBusy(el, false);
    }
}

document.addEventListener('click', (event) => {
    const el = event.target.closest('[data-action-url]');
    if (el && !(el instanceof HTMLInputElement)) {
        event.preventDefault();
        handleAction(el);
    }
});

document.addEventListener('change', (event) => {
    const el = event.target.closest('input[data-action-url]');
    if (el) {
        handleAction(el);
    }
});
