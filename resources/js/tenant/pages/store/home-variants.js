import '@tenant-css/pages/home-variants.css';
import { post } from '@tenant/core/http.js';

const form = document.querySelector('[data-hv-switcher-form]');
const themeSelect = form?.querySelector('[data-hv-theme-select]');

// Theme is a plain GET param: reload the page with the new query string,
// matching selectTheme() before.
themeSelect?.addEventListener('change', () => form.submit());

const scope = document.querySelector('[data-hv-scope]');
const themeId = scope?.dataset.themeId;
const selectUrl = scope?.dataset.selectUrl;

// The variant dropdown auto-saves on change (no separate submit button),
// matching the current wire:change UX exactly.
scope?.querySelectorAll('[data-hv-variant-select]').forEach((select) => {
    select.addEventListener('change', async (event) => {
        const target = event.target;
        const countryId = target.dataset.countryId || null;
        const variantId = target.value || null;

        try {
            await post(selectUrl, {
                theme_id: themeId,
                country_id: countryId,
                variant_id: variantId,
            });
        } catch {
            // Toast already shown by the http client's response interceptor.
        }
    });
});
