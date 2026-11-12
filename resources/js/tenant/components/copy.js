import { toast } from '../core/toast.js';
import { copyText } from '../core/clipboard.js';
import { t } from '../core/i18n.js';

// Copy button (x-tenant::copy). The value is read at click time so scripts can
// set data-copy-value after render (e.g. the product Share modal). On success the
// label briefly switches to data-copied-label ("Copied") as inline feedback.
export function init(el) {
    if (el._copyBound) {
        return;
    }
    el._copyBound = true;

    const labelEl = el.querySelector('[data-copy-label]');
    let resetTimer = null;

    el.addEventListener('click', async () => {
        const value = el.dataset.copyValue || '';

        if (!value) {
            toast.error(t('Nothing to copy.'));
            return;
        }

        if (await copyText(value)) {
            if (labelEl) {
                if (!el.dataset.originalLabel) {
                    el.dataset.originalLabel = labelEl.textContent;
                }
                labelEl.textContent = el.dataset.copiedLabel || t('Copied');
                el.classList.add('is-copied');
                clearTimeout(resetTimer);
                resetTimer = setTimeout(() => {
                    labelEl.textContent = el.dataset.originalLabel;
                    el.classList.remove('is-copied');
                }, 2000);
            }
            toast.success(el.dataset.copiedLabel || t('Copied'));
        } else {
            toast.error(t('Could not copy to clipboard.'));
        }
    });
}
