import { get } from './http.js';
import { emit } from './events.js';
import { initComponents } from './registry.js';

let lastFocused = null;

function getModal(id) {
    return document.getElementById(id);
}

function focusables(modal) {
    return Array.from(
        modal.querySelectorAll(
            'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])',
        ),
    ).filter((el) => el.offsetParent !== null);
}

function trapFocus(event, modal) {
    if (event.key !== 'Tab') {
        return;
    }

    const items = focusables(modal);
    if (!items.length) {
        return;
    }

    const first = items[0];
    const last = items[items.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

function onKeydown(event) {
    const openModal = document.querySelector('[data-tenant-modal].is-open');
    if (!openModal) {
        return;
    }

    if (event.key === 'Escape' && !openModal.dataset.static) {
        closeModal(openModal.id);
        return;
    }

    trapFocus(event, openModal);
}

export async function openModal(id, { url, fill, mode, action } = {}) {
    const modal = getModal(id);
    if (!modal) {
        return;
    }

    lastFocused = document.activeElement;

    const form = modal.querySelector('form');

    // Remember the form's original action/method the first time the modal is
    // opened so a later "create" open (no action/mode override) can restore
    // them after a previous "edit" open pointed the form elsewhere.
    if (form && form.dataset.defaultAction === undefined) {
        form.dataset.defaultAction = form.getAttribute('action') || '';
        form.dataset.defaultMethod = form.dataset.method || '';
    }

    if (form) {
        form.setAttribute('action', action || form.dataset.defaultAction);
        form.action = action || form.dataset.defaultAction;
        const resolvedMethod = mode || form.dataset.defaultMethod;
        if (resolvedMethod) {
            form.dataset.method = resolvedMethod;
        } else {
            delete form.dataset.method;
        }
    }

    if (url) {
        try {
            const response = await get(url, {}, { toast: false });
            if (form) {
                const { TenantForm } = await import('./forms.js');
                TenantForm.for(form).fill(response.data ?? {});
            }
        } catch {
            /* handled by http error interceptor */
        }
    } else if (fill) {
        if (form) {
            const { TenantForm } = await import('./forms.js');
            TenantForm.for(form).fill(fill);
        }
    } else if (form) {
        // No prefill source: this is a "create" open — reset any leftover
        // values/errors from a previous "edit" open of the same modal.
        const { TenantForm } = await import('./forms.js');
        TenantForm.for(form).reset();
    }

    modal.classList.add('is-open');
    modal.hidden = false;
    document.body.style.overflow = 'hidden';

    await initComponents(modal);

    const items = focusables(modal);
    items[0]?.focus();

    emit('tenant:modal:opened', { id });
}

export function closeModal(id) {
    const modal = getModal(id);
    if (!modal) {
        return;
    }

    modal.classList.remove('is-open');
    modal.hidden = true;
    document.body.style.overflow = '';

    if (!modal.dataset.keep) {
        const form = modal.querySelector('form');
        if (form) {
            import('./forms.js').then(({ TenantForm }) => TenantForm.for(form).reset());
        }
    }

    lastFocused?.focus?.();
    emit('tenant:modal:closed', { id });
}

function bindDelegation() {
    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-modal-open]');
        if (opener) {
            const id = opener.dataset.modalOpen;
            openModal(id, {
                url: opener.dataset.modalFillUrl,
                mode: opener.dataset.modalMethod || opener.dataset.modalMode,
                action: opener.dataset.modalAction,
            });
            return;
        }

        const closer = event.target.closest('[data-modal-close]');
        if (closer) {
            const modal = closer.closest('[data-tenant-modal]');
            if (modal) {
                closeModal(modal.id);
            }
            return;
        }

        const backdrop = event.target.closest('[data-tenant-modal]');
        if (backdrop && event.target === backdrop && !backdrop.dataset.static) {
            closeModal(backdrop.id);
        }
    });

    document.addEventListener('keydown', onKeydown);
}

bindDelegation();

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-tenant-modal][data-auto-open]').forEach((modal) => {
        openModal(modal.id);
    });
});
