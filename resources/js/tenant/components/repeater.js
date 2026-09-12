import { initComponents } from '../core/registry.js';

function reindexRow(row, index) {
    row.dataset.repeaterIndex = String(index);

    row.querySelectorAll('[name]').forEach((el) => {
        el.name = el.name.replace(/__INDEX__/g, String(index));
    });

    row.querySelectorAll('[data-field]').forEach((el) => {
        el.dataset.field = el.dataset.field.replace(/__INDEX__/g, String(index));
    });

    row.querySelectorAll('[data-error-for]').forEach((el) => {
        el.dataset.errorFor = el.dataset.errorFor.replace(/__INDEX__/g, String(index));
    });

    row.querySelectorAll('[id]').forEach((el) => {
        el.id = el.id.replace(/__INDEX__/g, String(index));
    });
}

function addRow(el, data = null) {
    const template = el.querySelector('[data-repeater-template]');
    const rows = el.querySelector('[data-repeater-rows]');

    if (!template || !rows) {
        return;
    }

    const index = rows.children.length;
    const fragment = template.content.cloneNode(true);
    const row = document.createElement('div');
    row.className = 't-repeater-row';
    row.appendChild(fragment);

    reindexRow(row, index);
    rows.appendChild(row);

    if (data) {
        Object.entries(data).forEach(([key, value]) => {
            const field = row.querySelector(`[name$="[${key}]"], [name="${key}"]`);
            if (field) {
                field.value = value ?? '';
            }
        });
    }

    initComponents(row);

    const removeBtn = row.querySelector('[data-repeater-remove]');
    removeBtn?.addEventListener('click', () => {
        row.remove();
        reindexAll(el);
    });

    el.dispatchEvent(new Event('change', { bubbles: true }));
}

function reindexAll(el) {
    const rows = el.querySelector('[data-repeater-rows]');
    if (!rows) {
        return;
    }
    Array.from(rows.children).forEach((row, index) => reindexRow(row, index));
}

export async function init(el) {
    const addBtn = el.querySelector('[data-repeater-add]');
    const itemsScript = el.querySelector('[data-repeater-items]');
    const min = parseInt(el.dataset.min || '0', 10);

    addBtn?.addEventListener('click', () => addRow(el));

    if (itemsScript) {
        try {
            const items = JSON.parse(itemsScript.textContent || '[]');
            items.forEach((item) => addRow(el, item));
        } catch {
            /* ignore malformed initial data */
        }
    }

    while (el.querySelectorAll('[data-repeater-rows] > .t-repeater-row').length < min) {
        addRow(el);
    }

    if (el.dataset.sortable === 'true') {
        const { default: Sortable } = await import('sortablejs');
        const rows = el.querySelector('[data-repeater-rows]');
        if (rows) {
            Sortable.create(rows, {
                handle: '.t-drag',
                animation: 150,
                onEnd: () => reindexAll(el),
            });
        }
    }
}
