import { debounce } from '../core/dom.js';
import { emit } from '../core/events.js';

function collect(el) {
    const values = {};

    el.querySelectorAll('[name]').forEach((field) => {
        const name = field.name.replace(/\[\]$/, '');

        if (field.type === 'checkbox') {
            if (!field.checked) {
                return;
            }
        }

        if (field.type === 'radio' && !field.checked) {
            return;
        }

        let value = field.value;

        if (field.tagName === 'SELECT' && field.multiple) {
            value = Array.from(field.selectedOptions).map((o) => o.value);
        }

        if (value === '' || value === null || (Array.isArray(value) && value.length === 0)) {
            return;
        }

        if (Object.prototype.hasOwnProperty.call(values, name)) {
            values[name] = [].concat(values[name], value);
        } else {
            values[name] = value;
        }
    });

    return values;
}

function target(el) {
    const id = el.dataset.target;
    return id ? document.getElementById(id) : null;
}

function updatePill(el, filters) {
    const pill = el.querySelector('[data-filters-count]');
    if (!pill) {
        return;
    }

    const count = Object.keys(filters).length;
    pill.hidden = count === 0;
    pill.textContent = `${count} active`;
}

function updateUrl(el, filters) {
    if (el.dataset.syncUrl !== '1') {
        return;
    }

    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
        if (Array.isArray(value)) {
            value.forEach((v) => params.append(`${key}[]`, v));
        } else {
            params.set(key, value);
        }
    });

    const query = params.toString();
    const url = query ? `${location.pathname}?${query}` : location.pathname;
    history.replaceState(history.state, '', url);
}

function updateExportLink(el, filters) {
    const selector = el.dataset.exportLink;
    if (!selector) {
        return;
    }

    const link = document.querySelector(selector);
    if (!link) {
        return;
    }

    const [base] = link.href.split('?');
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
        if (Array.isArray(value)) {
            value.forEach((v) => params.append(`filters[${key}][]`, v));
        } else {
            params.set(`filters[${key}]`, value);
        }
    });

    const query = params.toString();
    link.href = query ? `${base}?${query}` : base;
}

function apply(el, resetPaging = true) {
    const filters = collect(el);
    const targetEl = target(el);

    if (targetEl) {
        targetEl._filters = filters;
    }

    updatePill(el, filters);
    updateUrl(el, filters);
    updateExportLink(el, filters);

    if (el.dataset.target) {
        emit('tenant:table:reload', { selector: `#${el.dataset.target}`, resetPaging });
    }
}

export function init(el) {
    const debouncedApply = debounce(() => apply(el, true), 350);

    el.querySelectorAll('input[type="text"], input[type="search"], input[type="number"]').forEach((input) => {
        input.addEventListener('input', debouncedApply);
    });

    el.querySelectorAll('select, input[type="checkbox"], input[type="radio"], input[type="date"]').forEach((input) => {
        input.addEventListener('change', () => apply(el, true));
    });

    el.addEventListener('change', (event) => {
        if (event.target.matches('[data-tenant-select2]') || event.target.closest('[data-tenant-flatpickr]')) {
            apply(el, true);
        }
    });

    el.querySelector('[data-filters-reset]')?.addEventListener('click', (event) => {
        event.preventDefault();

        el.querySelectorAll('input[type="text"], input[type="search"], input[type="number"]').forEach((input) => {
            input.value = '';
        });
        el.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach((input) => {
            input.checked = false;
        });
        el.querySelectorAll('select[data-tenant-select2]').forEach((select) => {
            if (window.jQuery?.fn.select2) {
                window.jQuery(select).val(null).trigger('change');
            } else {
                select.value = '';
            }
        });
        el.querySelectorAll('select:not([data-tenant-select2])').forEach((select) => {
            select.selectedIndex = -1;
        });
        el.querySelectorAll('[data-tenant-flatpickr]').forEach((input) => {
            input._flatpickr?.clear();
        });

        apply(el, true);
    });

    apply(el, false);
}
