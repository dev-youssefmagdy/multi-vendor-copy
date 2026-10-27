// Banners page — the drag-to-reorder list, locale-tab image upload modal and
// create/edit/delete flow are declarative via x-tenant:: components. The only
// bespoke bit: the country filter is a plain GET form that submits on change.
document.querySelectorAll('.banner-country-filter select').forEach((select) => {
    select.addEventListener('change', () => select.form.submit());
});
