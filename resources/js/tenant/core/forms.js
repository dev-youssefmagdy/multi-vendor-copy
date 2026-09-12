import { post, put, patch, del, request } from './http.js';
import { confirm } from './confirm.js';
import { toast } from './toast.js';
import { emit } from './events.js';
import { debounce } from './dom.js';

const instances = new WeakMap();

function methodFor(form) {
    return (form.dataset.method || form.method || 'POST').toUpperCase();
}

function sender(method) {
    switch (method) {
        case 'PUT':
            return put;
        case 'PATCH':
            return patch;
        case 'DELETE':
            return del;
        default:
            return post;
    }
}

function fieldWrapper(form, key) {
    return form.querySelector(`[data-field="${CSS.escape(key)}"]`);
}

function errorSlot(form, key) {
    return form.querySelector(`[data-error-for="${CSS.escape(key)}"]`);
}

function ancestorKeys(key) {
    const parts = key.split('.');
    const keys = [];
    for (let i = parts.length - 1; i > 0; i -= 1) {
        keys.push(parts.slice(0, i).join('.'));
    }
    return keys;
}

export class TenantForm {
    constructor(form) {
        this.form = form;
        this.touched = new Set();
        this.initialData = new FormData(form);
        this.abortController = null;

        this.bind();
    }

    static for(form) {
        if (!instances.has(form)) {
            instances.set(form, new TenantForm(form));
        }
        return instances.get(form);
    }

    bind() {
        const debouncedValidate = debounce(() => this.validate({ only: this.touched }), 350);

        this.form.addEventListener('focusout', (e) => {
            const key = e.target?.name;
            if (key) {
                this.touched.add(this.normalizeKey(key));
                debouncedValidate();
            }
        });

        this.form.addEventListener('change', (e) => {
            const key = e.target?.name;
            if (key) {
                this.touched.add(this.normalizeKey(key));
                debouncedValidate();
            }
        });

        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submit();
        });
    }

    normalizeKey(name) {
        return name.replace(/\[(\w*)\]/g, '.$1').replace(/\.$/, '').replace(/^\./, '');
    }

    buildFormData({ excludeFiles = false } = {}) {
        const formData = new FormData(this.form);
        if (!excludeFiles) {
            return formData;
        }

        const filtered = new FormData();
        for (const [key, value] of formData.entries()) {
            if (!(value instanceof File)) {
                filtered.append(key, value);
            }
        }
        return filtered;
    }

    async validate({ only } = {}) {
        const url = this.form.dataset.validateUrl;
        if (!url) {
            return;
        }

        this.abortController?.abort();
        this.abortController = new AbortController();

        const formData = this.buildFormData({ excludeFiles: true });

        try {
            await request('post', url, formData, {
                toast: false,
                silent: true,
                isFormAction: true,
                signal: this.abortController.signal,
            });

            const keys = only ? Array.from(only) : [];
            keys.forEach((key) => this.clearFieldError(key));
        } catch (error) {
            if (error?.status === 422) {
                const errors = error.errors || {};
                const keys = only ? Array.from(only) : Object.keys(errors);
                keys.forEach((key) => {
                    if (errors[key]) {
                        this.setFieldError(key, errors[key][0]);
                    } else {
                        this.clearFieldError(key);
                    }
                });
            }
        }
    }

    /**
     * Runs every registered component's pre-submit hook (select2/phone/editor
     * sync, or a payment form's card tokenisation). Returning `false` from a
     * hook aborts the submit — used by the payment gateway modal to stop a
     * submission when card tokenisation fails.
     */
    async syncComponents() {
        if (window.tinymce) {
            this.form.querySelectorAll('[data-tenant-editor]').forEach((el) => {
                window.tinymce.get(el.id)?.save();
            });
        }

        const beforeSubmitEls = this.form.querySelectorAll('[data-tenant-ready]');
        for (const el of beforeSubmitEls) {
            const result = await el._tenantBeforeSubmit?.();
            if (result === false) {
                return false;
            }
        }

        return true;
    }

    async submit() {
        const confirmText = this.form.dataset.confirm;
        if (confirmText) {
            const ok = await confirm({ text: confirmText, danger: this.form.dataset.confirmDanger !== undefined });
            if (!ok) {
                return;
            }
        }

        const ready = await this.syncComponents();
        if (ready === false) {
            return;
        }

        const method = methodFor(this.form);
        const action = this.form.action;
        const formData = this.buildFormData();
        const send = sender(method);

        const submitButtons = this.form.querySelectorAll('[type="submit"]');
        submitButtons.forEach((btn) => {
            btn.disabled = true;
            btn.classList.add('is-loading');
        });
        this.form.classList.add('is-loading');

        try {
            const response = await send(action, formData, { isFormAction: true });
            this.clearErrors();
            this.applySuccess(response);
            emit('tenant:form:success', { form: this.form, response });
        } catch (error) {
            if (error?.status === 422) {
                const errors = error.errors || {};
                Object.keys(errors).forEach((key) => this.touched.add(key));
                this.setErrors(errors);
                toast.error(error.message || 'Please fix the highlighted fields.');
                this.focusFirstInvalid();
            }
            emit('tenant:form:error', { form: this.form, error });
        } finally {
            submitButtons.forEach((btn) => {
                btn.disabled = false;
                btn.classList.remove('is-loading');
            });
            this.form.classList.remove('is-loading');
        }
    }

    applySuccess(response) {
        const behaviours = (this.form.dataset.success || '').split(/\s+/).filter(Boolean);

        behaviours.forEach((behaviour) => {
            if (behaviour === 'reset') {
                this.reset();
            } else if (behaviour === 'close-modal') {
                const modal = this.form.closest('[data-tenant-modal]');
                if (modal) {
                    import('./modals.js').then(({ closeModal }) => closeModal(modal.id));
                }
            } else if (behaviour.startsWith('reload-table:')) {
                const selector = behaviour.slice('reload-table:'.length);
                emit('tenant:table:reload', { selector });
            } else if (behaviour === 'redirect') {
                if (response?.redirect) {
                    location.assign(response.redirect);
                }
            } else if (behaviour === 'reload-page') {
                location.reload();
            } else if (behaviour.startsWith('emit:')) {
                emit(behaviour.slice('emit:'.length), { response });
            } else if (behaviour === 'fill') {
                this.fill(response?.data ?? {});
            }
        });
    }

    focusFirstInvalid() {
        const invalid = this.form.querySelector('.is-invalid');
        if (!invalid) {
            return;
        }

        const hiddenParent = invalid.closest('[hidden], .is-tab-hidden');
        if (hiddenParent) {
            emit('tenant:reveal', { target: invalid });
        }

        invalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        invalid.focus?.();
    }

    setFieldError(key, message) {
        const wrapper = fieldWrapper(this.form, key);
        const slot = errorSlot(this.form, key);

        wrapper?.classList.add('is-invalid');
        wrapper?.querySelector('[required], input, select, textarea')?.setAttribute('aria-invalid', 'true');
        if (slot) {
            slot.textContent = message;
            slot.hidden = false;
        }
    }

    clearFieldError(key) {
        const wrapper = fieldWrapper(this.form, key);
        const slot = errorSlot(this.form, key);

        wrapper?.classList.remove('is-invalid');
        wrapper?.querySelector('[aria-invalid]')?.removeAttribute('aria-invalid');
        if (slot) {
            slot.textContent = '';
            slot.hidden = true;
        }
    }

    resolveErrorTarget(key) {
        if (fieldWrapper(this.form, key)) {
            return key;
        }

        for (const ancestor of ancestorKeys(key)) {
            if (fieldWrapper(this.form, ancestor)) {
                return ancestor;
            }
        }

        return null;
    }

    setErrors(errors) {
        const summary = this.form.querySelector('[data-form-errors]');
        const messages = [];

        Object.entries(errors).forEach(([key, list]) => {
            const target = this.resolveErrorTarget(key);
            const message = Array.isArray(list) ? list[0] : list;
            if (target) {
                this.setFieldError(target, message);
            }
            messages.push(message);
        });

        if (summary) {
            summary.textContent = messages.join(' ');
            summary.hidden = messages.length === 0;
        }
    }

    clearErrors() {
        this.form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        this.form.querySelectorAll('[data-error-for]').forEach((el) => {
            el.textContent = '';
            el.hidden = true;
        });
        const summary = this.form.querySelector('[data-form-errors]');
        if (summary) {
            summary.textContent = '';
            summary.hidden = true;
        }
    }

    fill(data, prefix = '') {
        Object.entries(data ?? {}).forEach(([key, value]) => {
            const fullKey = prefix ? `${prefix}.${key}` : key;

            // Companion "<field>_options" map ({id: label}) for an ajax-mode
            // multi select2: inject the missing <option> elements before the
            // matching "<field>" array key (processed next, in insertion
            // order) marks them selected. This is how a shared create/edit
            // modal pre-renders existing selections for a select2 field whose
            // options are otherwise only ever loaded by the ajax search.
            // Companion "<field>_current" URL for an x-tenant::image-upload
            // field: updates that component's preview <img> to the record's
            // existing image when a shared create/edit modal is filled for
            // edit (the component only ever has the file input's value to
            // work with otherwise, and a file input's value can't be set
            // from JS).
            if (key.endsWith('_current')) {
                const baseKey = prefix ? `${prefix}.${key.slice(0, -'_current'.length)}` : key.slice(0, -'_current'.length);
                const input = this.form.querySelector(`input[type="file"][name="${this.toInputName(baseKey)}"]`);
                const preview = input?.closest('[data-image-upload]')?.querySelector('.t-image-upload-preview');
                if (preview) {
                    if (value) {
                        preview.src = value;
                        preview.hidden = false;
                    } else {
                        preview.hidden = true;
                    }
                }
                return;
            }

            if (key.endsWith('_options') && value !== null && typeof value === 'object' && !Array.isArray(value)) {
                const baseKey = prefix ? `${prefix}.${key.slice(0, -'_options'.length)}` : key.slice(0, -'_options'.length);
                const select = this.form.querySelector(`select[data-tenant-select2][name="${this.toInputName(baseKey)}[]"]`);
                if (select) {
                    Object.entries(value).forEach(([optId, optText]) => {
                        if (!select.querySelector(`option[value="${CSS.escape(String(optId))}"]`)) {
                            const opt = document.createElement('option');
                            opt.value = optId;
                            opt.textContent = optText;
                            select.appendChild(opt);
                        }
                    });
                }
                return;
            }

            if (value !== null && typeof value === 'object' && !Array.isArray(value)) {
                this.fill(value, fullKey);
                return;
            }

            const selector = `[name="${this.toInputName(fullKey)}"]`;
            const fields = this.form.querySelectorAll(selector);

            fields.forEach((field) => {
                if (field.type === 'checkbox') {
                    field.checked = Array.isArray(value) ? value.includes(field.value) : Boolean(value);
                } else if (field.type === 'radio') {
                    field.checked = String(field.value) === String(value);
                } else if (field.tagName === 'SELECT' && field.multiple && Array.isArray(value)) {
                    Array.from(field.options).forEach((opt) => {
                        opt.selected = value.map(String).includes(opt.value);
                    });
                } else {
                    field.value = value ?? '';
                }

                field.dispatchEvent(new Event('change', { bubbles: true }));
            });

            if (window.tinymce) {
                const editorId = `f-${fullKey.replace(/\./g, '-')}`;
                const editor = window.tinymce.get(editorId);
                if (editor) {
                    editor.setContent(value ?? '');
                }
            }

            if (window.jQuery?.fn.select2) {
                const select2Field = this.form.querySelector(`select[data-tenant-select2][name="${this.toInputName(fullKey)}"], select[data-tenant-select2][name="${this.toInputName(fullKey)}[]"]`);
                if (select2Field) {
                    window.jQuery(select2Field).trigger('change');
                }
            }
        });
    }

    toInputName(dotKey) {
        const parts = dotKey.split('.');
        return parts.reduce((acc, part, i) => (i === 0 ? part : `${acc}[${part}]`), '');
    }

    reset() {
        this.form.reset();
        this.clearErrors();
        this.touched.clear();
    }
}

export function bindForms(root = document) {
    root.querySelectorAll('[data-tenant-form]').forEach((form) => TenantForm.for(form));
}
