import { toast } from '@tenant/core/toast.js';

document.getElementById('ui-kit-rtl-toggle')?.addEventListener('click', () => {
    const html = document.documentElement;
    html.dir = html.dir === 'rtl' ? 'ltr' : 'rtl';
});

document.addEventListener('DOMContentLoaded', () => {
    toast.info('UI kit loaded — try the toasts, forms, and datatable below.');
});
