import { toast } from '../core/toast.js';
import { copyText } from '../core/clipboard.js';

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
            toast.error('Nothing to copy.');
            return;
        }

        if (await copyText(value)) {
            if (labelEl) {
                if (!el.dataset.originalLabel) {
                    el.dataset.originalLabel = labelEl.textContent;
                }
                labelEl.textContent = el.dataset.copiedLabel || 'Copied';
                el.classList.add('is-copied');
                clearTimeout(resetTimer);
                resetTimer = setTimeout(() => {
                    labelEl.textContent = el.dataset.originalLabel;
                    el.classList.remove('is-copied');
                }, 2000);
            }
            toast.success(el.dataset.copiedLabel || 'Copied');
        } else {
            toast.error('Could not copy to clipboard.');
        }
    });
}
