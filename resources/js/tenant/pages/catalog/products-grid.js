// Products tab card grid: filters apply on change, and image-search results
// reload the grid with the matching products (via ?image_ids=…).
import '@tenant-css/pages/dashboard.css';
import '@tenant-css/pages/todays-chances.css';
import { on } from '../../core/events.js';

document.querySelectorAll('[data-pm-autosubmit]').forEach((select) => {
    select.addEventListener('change', () => select.form?.submit());
});

on('tenant:image-search:results', ({ ids }) => {
    const url = new URL(window.location.href);
    url.searchParams.delete('page');
    if (ids && ids.length) {
        url.searchParams.set('image_ids', ids.join(','));
    } else {
        url.searchParams.delete('image_ids');
    }
    window.location.assign(url.toString());
});
