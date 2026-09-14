import { request } from '../core/http.js';

export async function init(el) {
    const { default: Sortable } = await import('sortablejs');
    const items = el.querySelector('[data-sortable-items]');
    const saveBtn = el.querySelector('[data-sortable-save]');
    const autosave = el.dataset.autosave === '1';
    const saveUrl = el.dataset.saveUrl;
    const method = (el.dataset.method || 'POST').toLowerCase();
    const payloadKey = el.dataset.payloadKey || 'ids';

    if (!items) {
        return;
    }

    let originalOrder = Array.from(items.children).map((el2) => el2.dataset.id);

    async function persist() {
        const ids = Array.from(items.children).map((el2) => el2.dataset.id);

        try {
            await request(method, saveUrl, { [payloadKey]: ids });
            originalOrder = ids;
        } catch {
            Array.from(items.children)
                .sort((a, b) => originalOrder.indexOf(a.dataset.id) - originalOrder.indexOf(b.dataset.id))
                .forEach((child) => items.appendChild(child));
        }
    }

    Sortable.create(items, {
        handle: el.dataset.handle || '.t-drag',
        animation: 150,
        onEnd() {
            if (autosave) {
                persist();
            } else if (saveBtn) {
                saveBtn.hidden = false;
            }
        },
    });

    saveBtn?.addEventListener('click', async () => {
        await persist();
        saveBtn.hidden = true;
    });
}
