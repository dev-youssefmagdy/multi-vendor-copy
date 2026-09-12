import loadDataTables from '../vendor/datatables.js';
import { toast } from '../core/toast.js';
import { on } from '../core/events.js';
import { initComponents } from '../core/registry.js';
import { request } from '../core/http.js';
import { confirm } from '../core/confirm.js';

function initialFiltersFromQuery() {
    const params = new URLSearchParams(location.search);
    const filters = {};
    params.forEach((value, key) => {
        if (key.endsWith('[]')) {
            const base = key.slice(0, -2);
            filters[base] = (filters[base] || []).concat(value);
        } else {
            filters[key] = value;
        }
    });
    return filters;
}

function buildLanguage(config) {
    return {
        processing: 'Loading…',
        emptyTable: `<div class="empty-state"><div class="empty-state-title">${config.emptyTitle}</div><p class="empty-state-copy">${config.emptyCopy}</p></div>`,
        zeroRecords: `<div class="empty-state"><div class="empty-state-title">${config.emptyTitle}</div><p class="empty-state-copy">${config.emptyCopy}</p></div>`,
        info: 'Showing _START_ to _END_ of _TOTAL_ records',
        infoEmpty: 'Showing 0 to 0 of 0 records',
        infoFiltered: '',
        lengthMenu: '_MENU_ per page',
        paginate: { previous: '‹', next: '›' },
    };
}

function updateDescription(el, config, info) {
    const card = el.closest('[data-tenant-datatable-card]');
    const descEl = card?.querySelector('[data-table-description]');
    if (descEl && config.descriptionTemplate && config.descriptionTemplate.includes(':count')) {
        descEl.textContent = config.descriptionTemplate.replace(':count', info.recordsDisplay ?? 0);
    }
}

function bulkBar(el) {
    const card = el.closest('[data-tenant-datatable-card]');
    return card?.querySelector('[data-bulk-bar]');
}

function selectedIds(el) {
    return Array.from(el.querySelectorAll('tbody input[data-row-select]:checked')).map((cb) => cb.value);
}

function updateBulkBar(el) {
    const bar = bulkBar(el);
    if (!bar) {
        return;
    }
    const ids = selectedIds(el);
    bar.hidden = ids.length === 0;
    const countEl = bar.querySelector('[data-bulk-count]');
    if (countEl) {
        countEl.textContent = `${ids.length} selected`;
    }
}

export async function init(el) {
    const DataTable = await loadDataTables();
    const config = JSON.parse(el.dataset.config || '{}');
    const card = el.closest('[data-tenant-datatable-card]');

    DataTable.ext.errMode = 'none';

    const storageKey = `dt:len:${el.id}`;
    const storedLength = parseInt(localStorage.getItem(storageKey) || '', 10);
    const pageLength = Number.isFinite(storedLength) && storedLength > 0 ? storedLength : (config.pageLength || 10);

    const columns = config.columns.map((col) => ({
        data: col.data,
        name: col.name,
        title: col.title,
        orderable: col.orderable !== false,
        searchable: false,
        className: col.className,
        width: col.width,
        responsivePriority: col.responsivePriority,
        visible: col.visible !== false,
    }));

    if (config.selectable) {
        columns.unshift({
            data: null,
            orderable: false,
            searchable: false,
            className: 't-select-col',
            render: (data, type, row) => `<input type="checkbox" data-row-select value="${row.id ?? row.uuid ?? ''}">`,
        });
    }

    const options = {
        columns,
        order: config.order || [[0, 'asc']],
        pageLength,
        lengthMenu: [10, 25, 50, 100],
        serverSide: config.mode !== 'client',
        processing: true,
        responsive: true,
        autoWidth: false,
        searching: Boolean(config.searching),
        paging: config.paging !== false,
        deferRender: true,
        language: buildLanguage(config),
        layout: {
            topStart: null,
            topEnd: null,
            bottomStart: 'info',
            bottomEnd: 'paging',
            bottom2Start: 'pageLength',
        },
        drawCallback() {
            initComponents(el.closest('.tw') || el);
            updateBulkBar(el);
            el.dispatchEvent(new CustomEvent('tenant:table:drawn', { bubbles: true }));
        },
    };

    if (config.mode !== 'client') {
        options.ajax = {
            url: config.url,
            type: 'GET',
            data: (d) => {
                d.filters = el._filters || initialFiltersFromQuery();
            },
        };
    }

    const dt = new DataTable(el, options);
    el._dt = dt;

    const $el = window.jQuery(el);

    $el.on('length.dt', (e, settings, len) => {
        localStorage.setItem(storageKey, String(len));
    });

    $el.on('xhr.dt', (e, settings, json) => {
        if (json) {
            updateDescription(el, config, {
                recordsDisplay: json.recordsFiltered ?? json.recordsTotal,
            });
        }
    });

    $el.on('error.dt', () => {
        toast.error("Couldn't load the table. Please retry.");
    });

    on('tenant:table:reload', ({ selector, resetPaging }) => {
        if (selector && (selector === `#${el.id}` || selector === el.id)) {
            dt.ajax.reload(null, resetPaging !== false ? false : true);
        }
    });

    if (config.quickSearchBound !== true) {
        card?.querySelector('[data-table-quick-search]')?.addEventListener('input', (e) => {
            el._filters = { ...(el._filters || {}), search: e.target.value };
            dt.ajax.reload(null, false);
        });
    }

    if (config.selectable) {
        const selectAll = card?.querySelector('[data-select-all]');
        selectAll?.addEventListener('change', () => {
            el.querySelectorAll('tbody input[data-row-select]').forEach((cb) => {
                cb.checked = selectAll.checked;
            });
            updateBulkBar(el);
        });

        el.addEventListener('change', (e) => {
            if (e.target.matches('input[data-row-select]')) {
                updateBulkBar(el);
            }
        });

        card?.querySelector('[data-bulk-action]')?.addEventListener('click', async () => {
            const ids = selectedIds(el);
            if (!ids.length || !config.bulkUrl) {
                return;
            }

            if (config.bulkConfirm) {
                const ok = await confirm({ text: config.bulkConfirm, danger: true });
                if (!ok) {
                    return;
                }
            }

            try {
                await request(config.bulkMethod?.toLowerCase() || 'delete', config.bulkUrl, { ids });
                dt.ajax.reload();
            } catch {
                /* handled by http interceptor */
            }
        });
    }
}
