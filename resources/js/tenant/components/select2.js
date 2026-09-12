import loadSelect2 from '../vendor/select2.js';

const dependents = new WeakMap();

export async function init(el) {
    const $ = await loadSelect2();
    const $el = $(el);

    const dir = document.documentElement.dir || 'ltr';
    const ajaxUrl = el.dataset.ajaxUrl;
    const isTree = el.dataset.tree === 'true';
    const minInput = parseInt(el.dataset.minInput || '0', 10);
    const allowClear = el.dataset.allowClear === '1';
    const placeholder = el.dataset.placeholder || '';
    const dependsOn = el.dataset.dependsOn;
    const maxSelection = el.dataset.maxSelection ? parseInt(el.dataset.maxSelection, 10) : null;
    const closeOnSelect = el.dataset.closeOnSelect !== undefined ? el.dataset.closeOnSelect === '1' : undefined;

    const options = {
        width: '100%',
        dir,
        theme: 'default',
        minimumResultsForSearch: 0,
        allowClear,
        placeholder: placeholder || null,
        dropdownParent: $(el.closest('.t-modal') || document.body),
        tags: el.dataset.tags === 'true',
        language: {
            noResults: () => 'No results found',
            searching: () => 'Searching…',
            inputTooShort: (args) => `Please enter ${args.minimum} or more characters`,
        },
    };

    if (maxSelection) {
        options.maximumSelectionLength = maxSelection;
    }

    if (closeOnSelect !== undefined) {
        options.closeOnSelect = closeOnSelect;
    }

    if (ajaxUrl) {
        options.minimumInputLength = minInput;
        options.ajax = {
            delay: 250,
            url: ajaxUrl,
            data: (params) => ({
                q: params.term || '',
                page: params.page || 1,
                ...(dependsOn ? { depends_on: getDependencyValue(el, dependsOn) } : {}),
            }),
            processResults: (data, params) => ({
                results: (data.results || []).map((r) => ({ id: r.id, text: r.text, image: r.image, disabled: r.disabled })),
                pagination: { more: Boolean(data.pagination?.more) },
            }),
        };
    }

    if (isTree) {
        options.templateResult = (data) => {
            if (!data.id) {
                return data.text;
            }
            const level = parseInt($(data.element).data('level') || 0, 10);
            const span = document.createElement('span');
            span.style.paddingLeft = `${level * 14}px`;
            span.textContent = data.text;
            return span;
        };
    }

    $el.select2(options);

    $el.on('change', () => {
        el.dispatchEvent(new Event('change', { bubbles: true }));
    });

    if (dependsOn) {
        const form = el.closest('form');
        const source = form?.querySelector(`[name="${CSS.escape(dependsOn)}"]`);
        if (source) {
            const handler = () => {
                $el.val(null).trigger('change');
            };
            source.addEventListener('change', handler);
            dependents.set(el, { source, handler });
        }
    }

    el._tenantBeforeSubmit = () => {
        // select2 keeps the native <select> in sync already; nothing extra needed.
    };
}

function getDependencyValue(el, dependsOn) {
    const form = el.closest('form');
    return form?.querySelector(`[name="${CSS.escape(dependsOn)}"]`)?.value ?? '';
}

export function destroy(el) {
    if (window.jQuery?.fn.select2) {
        window.jQuery(el).select2('destroy');
    }
    const dep = dependents.get(el);
    if (dep) {
        dep.source.removeEventListener('change', dep.handler);
        dependents.delete(el);
    }
}
