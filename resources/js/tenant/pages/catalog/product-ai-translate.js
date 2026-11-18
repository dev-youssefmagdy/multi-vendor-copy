// "Translate with AI" on the vendor product forms (own-product-form.js, product-form.js).
// Each non-source locale pane has a [data-ai-translate] bar (tenant/pages/catalog/_ai-translate-bar.blade.php).
// The AI result is written into that pane's fields as a preview: nothing is saved until the
// vendor saves the product. Every generation (incl. Regenerate) is paid: one plan AI call.
// "Regenerate" asks for another version; "Undo" restores the
// values the fields had before the first AI run.
import { post } from '@tenant/core/http.js';
import { toast } from '@tenant/core/toast.js';
import { setBusy } from '@tenant/core/dom.js';
import { t } from '../../core/i18n.js';

const FIELDS = ['name', 'label', 'summary', 'description', 'meta_keywords', 'meta_description'];

function fieldEl(root, locale, field) {
    return root?.querySelector(`[name="translations[${locale}][${field}]"]`) ?? null;
}

function editorFor(el) {
    return el?.matches('[data-tenant-editor]') && el.id ? window.tinymce?.get(el.id) ?? null : null;
}

function readField(el) {
    const editor = editorFor(el);
    return editor ? editor.getContent() : (el?.value ?? '');
}

function writeField(el, value) {
    const editor = editorFor(el);
    if (editor) {
        editor.setContent(value ?? '');
        editor.save();
    } else {
        el.value = value ?? '';
    }
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
}

function setupBar(form, bar) {
    const pane = bar.closest('[data-locale-pane]');
    const runBtn = bar.querySelector('[data-ai-translate-run]');
    const undoBtn = bar.querySelector('[data-ai-translate-undo]');
    const hint = bar.querySelector('[data-ai-translate-hint]');
    const target = bar.dataset.targetLocale;
    const source = bar.dataset.sourceLocale;
    const sourcePane = form.querySelector(`[data-locale-pane="${source}"]`);
    const originalHint = hint?.textContent.trim() ?? '';
    let snapshot = null;
    let generated = false;

    // Only the fields this form actually translates (the platform form has no label/summary).
    const fields = FIELDS.filter((field) => fieldEl(pane, target, field));

    function setLabel() {
        runBtn.textContent = generated ? t('Regenerate') : t('Translate with AI');
    }

    runBtn?.addEventListener('click', async () => {
        if (bar.dataset.allowed !== '1') {
            toast.warning(bar.dataset.lockedMessage);
            return;
        }

        if (fields.length === 0) {
            return;
        }

        const sourceValues = {};
        fields.forEach((field) => {
            sourceValues[field] = readField(fieldEl(sourcePane, source, field));
        });

        setBusy(runBtn, true);
        runBtn.textContent = t('Translating…');

        try {
            const res = await post(bar.dataset.url, {
                target_locale: target,
                source_locale: source,
                product_id: bar.dataset.productId || null,
                fields,
                source: sourceValues,
                regenerate: generated,
            });

            const translations = res?.data?.translations ?? {};

            if (snapshot === null) {
                snapshot = {};
                fields.forEach((field) => {
                    snapshot[field] = readField(fieldEl(pane, target, field));
                });
            }

            Object.entries(translations).forEach(([field, value]) => {
                const el = fieldEl(pane, target, field);
                if (el) {
                    writeField(el, value);
                }
            });

            // Each generation is paid (plan AI calls): refresh every pane's "left" counter.
            if (res?.data?.remaining !== null && res?.data?.remaining !== undefined) {
                form.querySelectorAll('[data-ai-translate-remaining]').forEach((el) => {
                    el.textContent = t(':count AI calls left on your plan', { count: res.data.remaining });
                });
            }

            generated = true;
            if (undoBtn) undoBtn.hidden = false;
            if (hint) hint.textContent = t('AI preview: review or edit the fields below, then save the product to keep it. Not happy with it? Regenerate.');
        } catch {
            // The HTTP layer already showed the (translated) error message.
        } finally {
            setBusy(runBtn, false);
            setLabel();
        }
    });

    undoBtn?.addEventListener('click', () => {
        if (snapshot) {
            Object.entries(snapshot).forEach(([field, value]) => {
                const el = fieldEl(pane, target, field);
                if (el) {
                    writeField(el, value);
                }
            });
        }
        snapshot = null;
        generated = false;
        undoBtn.hidden = true;
        if (hint) hint.textContent = originalHint;
        setLabel();
    });
}

export function initProductAiTranslate(form) {
    form?.querySelectorAll('[data-ai-translate]').forEach((bar) => setupBar(form, bar));
}
