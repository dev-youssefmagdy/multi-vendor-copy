import { toast } from '../core/toast.js';

export function init(el) {
    el.addEventListener('click', async () => {
        const value = el.dataset.copyValue || '';

        try {
            await navigator.clipboard.writeText(value);
            toast.success('Copied');
        } catch {
            toast.error('Could not copy to clipboard.');
        }
    });
}
