import '@tenant-css/pages/page-builder.css';

const form = document.querySelector('[data-pb-switcher-form]');
const themeSelect = form?.querySelector('[data-pb-theme-select]');
const variantSelect = form?.querySelector('[data-pb-variant-select]');

// Theme/variant selectors are plain GET params: reload the page with the
// new query string whenever either select changes (progressive-enhancement
// over the <noscript> submit button).
themeSelect?.addEventListener('change', () => {
    // Changing the theme clears the variant param so the controller falls
    // back to that theme's default variant, matching selectTheme() before.
    if (variantSelect) {
        variantSelect.removeAttribute('name');
    }
    form.submit();
});

variantSelect?.addEventListener('change', () => form.submit());
