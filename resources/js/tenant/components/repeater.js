import { initComponents } from '../core/registry.js';

function tokenFor(el) {
    return el.dataset.indexToken || '__INDEX__';
}

// A repeater can be nested inside another repeater's row (e.g. per-variant
// option pairs inside a variant list). Plain querySelector('[data-x]') would
// happily match the same selector on a deeper, nested repeater instance
// before it reaches this repeater's own element, since nested rows sit
// earlier in document order than this repeater's trailing controls. Scope
// lookups to elements whose nearest repeater ancestor is genuinely `el`.
function ownMatch(el, selector, root = el) {
    return Array.from(root.querySelectorAll(selector)).find((candidate) => candidate.closest('[data-tenant-repeater]') === el) || null;
}

function escapeRegExp(s) {
    return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function reindexRow(row, index, token = '__INDEX__') {
    row.dataset.repeaterIndex = String(index);
    const re = new RegExp(escapeRegExp(token), 'g');

    row.querySelectorAll('[name]').forEach((el) => {
        el.name = el.name.replace(re, String(index));
    });

    row.querySelectorAll('[data-field]').forEach((el) => {
        el.dataset.field = el.dataset.field.replace(re, String(index));
    });

    row.querySelectorAll('[data-error-for]').forEach((el) => {
        el.dataset.errorFor = el.dataset.errorFor.replace(re, String(index));
    });

    row.querySelectorAll('[id]').forEach((el) => {
        el.id = el.id.replace(re, String(index));
    });

    // Nested repeater templates are inert <template> content — not reachable
    // via querySelectorAll on the row — so patch their serialized markup
    // directly, otherwise an outer index placeholder used by a nested
    // repeater's own template would never get resolved for future rows.
    row.querySelectorAll('template').forEach((tpl) => {
        tpl.innerHTML = tpl.innerHTML.replace(re, String(index));
    });
}

function addRow(el, data = null) {
    const template = el.querySelector('[data-repeater-template]');
    const rows = el.querySelector('[data-repeater-rows]');

    if (!template || !rows) {
        return;
    }

    const token = tokenFor(el);
    const index = rows.children.length;
    const fragment = template.content.cloneNode(true);
    const row = document.createElement('div');
    row.className = 't-repeater-row';
    row.appendChild(fragment);

    reindexRow(row, index, token);
    rows.appendChild(row);

    if (data) {
        Object.entries(data).forEach(([key, value]) => {
            const field = row.querySelector(`[name$="[${key}]"], [name="${key}"]`);
            if (field) {
                field.value = value ?? '';
                // Also remember the raw intended value on the element itself —
                // useful for fields whose available <option>s are populated
                // dynamically *after* hydration (e.g. a cascading select),
                // where the plain .value assignment above is a no-op because
                // the matching <option> doesn't exist yet.
                field.dataset.repeaterValue = value ?? '';
            }
        });
    }

    initComponents(row);

    const removeBtn = ownMatch(el, '[data-repeater-remove]', row);
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
    const token = tokenFor(el);
    Array.from(rows.children).forEach((row, index) => reindexRow(row, index, token));
}

export async function init(el) {
    const addBtn = ownMatch(el, '[data-repeater-add]');
    const itemsScript = ownMatch(el, '[data-repeater-items]');
    const min = parseInt(el.dataset.min || '0', 10);

    addBtn?.addEventListener('click', () => addRow(el));

    // Rows already present in the markup (server-rendered for existing
    // records) need their remove button wired up, since they were not
    // created through addRow().
    const rowsContainer = el.querySelector('[data-repeater-rows]');
    if (rowsContainer) {
        Array.from(rowsContainer.children).forEach((row) => {
            if (row.dataset.repeaterWired === '1') {
                return;
            }
            row.dataset.repeaterWired = '1';
            initComponents(row);
            const removeBtn = ownMatch(el, '[data-repeater-remove]', row);
            removeBtn?.addEventListener('click', () => {
                row.remove();
                reindexAll(el);
            });
        });
    }

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
