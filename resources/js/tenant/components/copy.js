import { toast } from '../core/toast.js';
import { copyText } from '../core/clipboard.js';

export function init(el) {
    el.addEventListener('click', async () => {
        const value = el.dataset.copyValue || '';

        if (await copyText(value)) {
            toast.success('Copied');
        } else {
            toast.error('Could not copy to clipboard.');
        }
    });
}
