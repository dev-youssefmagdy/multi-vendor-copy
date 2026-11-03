import '@tenant-css/pages/appearance.css';
import { post } from '@tenant/core/http.js';
import { confirm } from '@tenant/core/confirm.js';
import { toast } from '@tenant/core/toast.js';
import { setBusy } from '@tenant/core/dom.js';
import '@tenant/modules/logo-builder.js';

// ── Colors & Fonts tab ────────────────────────────────────────────────────────

/** Sets a color field's text + swatch the same way typing it would. */
function setColor(field, value) {
    const text = field.querySelector('[data-color-text]');
    const picker = field.querySelector('[data-color-picker]');
    if (!text) {
        return;
    }
    text.value = value;
    if (picker && /^#[0-9a-f]{6}$/i.test(value)) {
        picker.value = value;
    }
    text.dispatchEvent(new Event('change', { bubbles: true }));
}

function updateFontPreview(select) {
    const preview = select.closest('.t-field')?.querySelector('[data-font-preview]');
    const stack = select.selectedOptions[0]?.dataset.stack;
    if (preview && stack) {
        preview.style.fontFamily = stack;
    }
}

document.querySelectorAll('[data-font-select]').forEach((select) => {
    select.addEventListener('change', () => updateFontPreview(select));
});

// ── Live preview ──────────────────────────────────────────────────────────────
// The edition's form carries `data-roles` (preview role => token). A role is a slot of the mini storefront
// ("brand", "text", "page-bg"…); changing a token sets --pv-<role> on the form, and every illustration inside
// it (the scene + the per-group previews) reads those variables, so edits show instantly.
const COLOR_RE = /^#[0-9a-f]{3,8}$/i;

function initLivePreview(form) {
    const roles = JSON.parse(form.dataset.roles || '{}');
    const tokenRoles = {};
    Object.entries(roles).forEach(([role, token]) => (tokenRoles[token] ??= []).push(role));
    const inkFor = (hex) => {
        const h = hex.replace('#', '');
        const full = h.length <= 4 ? [...h].map((c) => c + c).join('') : h;
        const [r, g, b] = [0, 2, 4].map((i) => parseInt(full.slice(i, i + 2), 16));
        return (r * 299 + g * 587 + b * 114) / 1000 > 150 ? '#111111' : '#ffffff';
    };

    const apply = (token, value) => {
        if (!COLOR_RE.test(value)) {
            return;
        }
        (tokenRoles[token] ?? []).forEach((role) => {
            form.style.setProperty(`--pv-${role}`, value);
            if (role === 'brand-alt') {
                form.style.setProperty('--pv-brand-alt-ink', inkFor(value));
            }
            if (role === 'discount') {
                form.style.setProperty('--pv-discount-ink', inkFor(value));
            }
        });
        form.querySelectorAll(`[data-chip-for="${CSS.escape(token)}"]`).forEach((chip) => chip.style.setProperty('--c', value));
    };

    const tokenOf = (input) => input.name.match(/^values\[(.+)\]$/)?.[1];
    const syncColors = () => form.querySelectorAll('[data-color-text]').forEach((input) => {
        const token = tokenOf(input);
        if (token) {
            apply(token, input.value);
        }
    });
    const syncFonts = () => {
        ['ltr', 'rtl'].forEach((dir) => {
            const stack = form.querySelector(`select[name="fonts[${dir}]"]`)?.selectedOptions[0]?.dataset.stack;
            if (stack) {
                form.style.setProperty(`--pv-font-${dir}`, `${stack}, system-ui, sans-serif`);
            }
        });
    };
    form._syncPreview = () => { syncColors(); syncFonts(); };

    form.addEventListener('input', (event) => {
        const token = event.target.matches?.('[data-color-text]') ? tokenOf(event.target) : null;
        if (token) {
            apply(token, event.target.value);
        }
    });
    form.addEventListener('change', (event) => {
        const target = event.target;
        if (target.matches?.('[data-color-text]')) {
            const token = tokenOf(target);
            if (token) {
                apply(token, target.value);
            }
        } else if (target.matches?.('[data-font-select]')) {
            syncFonts();
        }
    });

    // Focus / hover a color field (or its chip) -> highlight the preview parts it changes.
    const highlight = (token, on) => {
        form.classList.toggle('t-design-form-hl', on);
        form.querySelectorAll('[data-pv].is-hl').forEach((el) => el.classList.remove('is-hl'));
        if (!on) {
            return;
        }
        (tokenRoles[token] ?? []).forEach((role) => {
            form.querySelectorAll('[data-pv]').forEach((el) => {
                if (el.dataset.pv.split(' ').includes(role)) {
                    el.classList.add('is-hl');
                }
            });
        });
    };
    form.querySelectorAll('[data-design-token]').forEach((row) => {
        const token = row.dataset.token;
        row.addEventListener('focusin', () => highlight(token, true));
        row.addEventListener('focusout', () => highlight(token, false));
        row.addEventListener('mouseenter', () => highlight(token, true));
        row.addEventListener('mouseleave', () => highlight(token, false));
    });
    form.querySelectorAll('[data-chip-focus]').forEach((chip) => {
        chip.addEventListener('click', () => {
            const row = form.querySelector(`[data-design-token][data-token="${CSS.escape(chip.dataset.chipFocus)}"]`);
            row?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row?.querySelector('[data-color-text]')?.focus({ preventScroll: true });
        });
    });

    // Scene: LTR / RTL toggle swaps direction, sample text and the font used.
    const scene = form.querySelector('[data-pv-scene]');
    form.querySelectorAll('[data-pv-dir]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const dir = btn.dataset.pvDir;
            form.querySelectorAll('[data-pv-dir]').forEach((b) => b.classList.toggle('is-active', b === btn));
            scene.dir = dir;
            scene.querySelectorAll('[data-pv-text]').forEach((el) => {
                const map = Object.fromEntries(el.dataset.pvText.split('|').map((part) => [part.slice(0, 3), part.slice(4)]));
                el.textContent = map[dir] ?? el.textContent;
            });
        });
    });

    form._syncPreview();
}

document.querySelectorAll('[data-design-form]').forEach(initLivePreview);

// Per-color "Default #xxxxxx" link.
document.querySelectorAll('[data-default-color]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const field = btn.closest('[data-design-token]');
        if (field) {
            setColor(field, btn.dataset.defaultColor);
        }
    });
});

// Search across every color of an edition: matches label, description, "affects" areas and the CSS variable.
document.querySelectorAll('[data-design-search]').forEach((input) => {
    const block = input.closest('.t-design-block');
    input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        let visible = 0;
        block.querySelectorAll('[data-design-token]').forEach((token) => {
            const match = q === '' || token.dataset.search.includes(q);
            token.hidden = !match;
            visible += match ? 1 : 0;
        });
        block.querySelectorAll('[data-design-group]').forEach((group) => {
            const any = [...group.querySelectorAll('[data-design-token]')].some((t) => !t.hidden);
            group.hidden = !any;
            if (q !== '' && any) {
                group.open = true;
            }
        });
        block.querySelector('[data-design-empty]').hidden = visible > 0;
    });
});

// "Reset to Default" resets the saved design on the server, then refills the form from the response
// instead of a full page reload, so the admin can review the defaults and still hit Save explicitly.
document.querySelectorAll('[data-reset-colors-url]').forEach((btn) => {
    btn.addEventListener('click', async () => {
        const ok = await confirm({ text: "Reset this edition's colors and fonts to the theme's default?" });
        if (!ok) {
            return;
        }

        const formId = btn.dataset.resetColorsForm;
        const form = formId ? document.getElementById(formId) : null;

        setBusy(btn, true);
        try {
            const response = await post(btn.dataset.resetColorsUrl, {}, { toast: true });
            if (form) {
                const { TenantForm } = await import('@tenant/core/forms.js');
                TenantForm.for(form).fill(response.data?.defaults ?? {}, 'values');
                Object.entries(response.data?.fonts ?? {}).forEach(([direction, key]) => {
                    const select = form.querySelector(`select[name="fonts[${direction}]"]`);
                    if (select) {
                        select.value = key;
                        updateFontPreview(select);
                    }
                });
                form._syncPreview?.();
            }
        } catch {
            // Handled by the http client's error interceptor.
        } finally {
            setBusy(btn, false);
        }
    });
});
