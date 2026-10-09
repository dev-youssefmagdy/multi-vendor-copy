import '@tenant-css/pages/own-products.css';

const form = document.getElementById('own-product-form');
if (form) {
    // ── Manage-stock toggle ──────────────────────────────────────────────────
    const manageStockToggle = form.querySelector('[data-manage-stock-toggle]');
    const stockFieldWraps = form.querySelectorAll('[data-stock-fields]');

    function syncStockFields() {
        const show = manageStockToggle ? manageStockToggle.checked : true;
        stockFieldWraps.forEach((el) => { el.hidden = !show; });
    }

    manageStockToggle?.addEventListener('change', syncStockFields);
    syncStockFields();

    // ── Country picker: filter the list as you type ─────────────────────────
    const countries = form.querySelector('[data-op-countries]');
    countries?.querySelector('[data-op-country-search]')?.addEventListener('input', (e) => {
        const term = e.target.value.trim().toLowerCase();
        let shown = 0;
        countries.querySelectorAll('[data-op-country]').forEach((item) => {
            item.hidden = term !== '' && !item.dataset.opCountry.includes(term);
            shown += item.hidden ? 0 : 1;
        });
        countries.querySelector('[data-op-country-empty]').hidden = shown > 0;
    });

    // ── Country picker: "Select all countries" ticks/unticks the countries the
    //    search currently shows (hidden ones keep their ticks) and mirrors them
    //    (checked when all shown are ticked, indeterminate when some) ──────────
    const selectAll = countries?.querySelector('[data-op-country-all]');
    if (selectAll) {
        const boxes = () => countries.querySelectorAll('input[name="countries[]"]');
        const shownBoxes = () => [...boxes()].filter((box) => !box.closest('[data-op-country]')?.hidden);
        const syncSelectAll = () => {
            const shown = shownBoxes();
            const ticked = shown.filter((box) => box.checked).length;
            selectAll.disabled = shown.length === 0;
            selectAll.checked = shown.length > 0 && ticked === shown.length;
            selectAll.indeterminate = ticked > 0 && ticked < shown.length;
        };
        selectAll.addEventListener('change', () => {
            shownBoxes().forEach((box) => { box.checked = selectAll.checked; });
            selectAll.indeterminate = false;
        });
        boxes().forEach((box) => box.addEventListener('change', syncSelectAll));
        countries.querySelector('[data-op-country-search]')?.addEventListener('input', syncSelectAll);
        syncSelectAll();
    }

    // ── Primary image thumbnail: red ✕ clears a new pick, or marks the saved
    //    image for removal (remove_primary_image) ─────────────────────────────
    const primary = form.querySelector('[data-op-primary]');
    if (primary) {
        const fileInput = primary.querySelector('input[type="file"]');
        const preview = primary.querySelector('.t-image-upload-preview');
        const removeBox = primary.querySelector('[data-op-primary-remove]');

        removeBox?.addEventListener('change', () => {
            if (fileInput.value) {
                fileInput.value = ''; // drop the new pick; the saved image stays
                removeBox.checked = false;
                preview.src = preview.dataset.savedSrc || '';
                preview.hidden = !preview.dataset.savedSrc;
            } else {
                preview.hidden = true;
            }
        });

        preview.dataset.savedSrc = preview.hidden ? '' : preview.getAttribute('src');
        fileInput?.addEventListener('change', () => {
            if (fileInput.files.length && removeBox) removeBox.checked = false;
        });
    }

    // ── Return policy override toggle ────────────────────────────────────────
    const returnPolicyToggle = form.querySelector('[data-return-policy-toggle]');
    const returnPolicyFields = form.querySelector('[data-return-policy-fields]');

    returnPolicyToggle?.addEventListener('change', () => {
        if (returnPolicyFields) {
            returnPolicyFields.hidden = !returnPolicyToggle.checked;
        }
    });

    // ── Variant numbering (renumber on add / remove / drag reorder) ─────────
    const variantsRows = form.querySelector('[data-tenant-repeater][data-name="variants"] [data-repeater-rows]');

    function renumberVariants() {
        if (!variantsRows) return;
        Array.from(variantsRows.children).forEach((row, index) => {
            const numEl = row.querySelector(':scope > .vrow [data-vrow-num]');
            if (numEl) numEl.textContent = '#' + (index + 1);
        });
    }

    if (variantsRows) {
        renumberVariants();
        new MutationObserver(renumberVariants).observe(variantsRows, { childList: true });
    }

    // ── Cascading variation → option pickers (variant option pairs) ─────────
    let variationGroups = {};
    try {
        variationGroups = JSON.parse(document.getElementById('variations-data')?.textContent || '{}');
    } catch {
        variationGroups = {};
    }

    function populateOptions(optionSelect, variationId, desiredValue) {
        optionSelect.innerHTML = '<option value="">Select option value</option>';
        const group = variationId ? variationGroups[variationId] : null;
        if (!group) return;

        group.options.forEach((opt) => {
            const option = document.createElement('option');
            option.value = String(opt.id);
            option.textContent = opt.name;
            if (desiredValue !== undefined && desiredValue !== null && String(desiredValue) === String(opt.id)) {
                option.selected = true;
            }
            optionSelect.appendChild(option);
        });
    }

    function pairRowOf(el) {
        return el.closest('.variant-pair-row');
    }

    form.addEventListener('change', (e) => {
        const variationSelect = e.target.closest('[data-pair-variation]');
        if (variationSelect) {
            const row = pairRowOf(variationSelect);
            const optionSelect = row?.querySelector('[data-pair-option]');
            if (optionSelect) {
                populateOptions(optionSelect, variationSelect.value || null);
                delete optionSelect.dataset.repeaterValue;
            }
        }
    });

    // Repeaters fire a bubbling "change" on their own container right after a
    // row is hydrated from initial JSON data — use that moment to populate any
    // option pickers whose variation is already selected (existing variants).
    form.addEventListener('change', (e) => {
        if (!e.target.matches('[data-tenant-repeater]')) {
            return;
        }
        e.target.querySelectorAll('[data-pair-option]').forEach((optionSelect) => {
            if (optionSelect.options.length > 1) {
                return; // already populated
            }
            const row = pairRowOf(optionSelect);
            const variationSelect = row?.querySelector('[data-pair-variation]');
            if (variationSelect?.value) {
                populateOptions(optionSelect, variationSelect.value, optionSelect.dataset.repeaterValue);
                delete optionSelect.dataset.repeaterValue;
            }
        });
    });

    // ── Variant thumbnail: remove existing image ─────────────────────────────
    form.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('[data-vrow-remove-image]');
        if (!removeBtn) return;

        const thumbWrap = removeBtn.closest('[data-vrow-thumb]');
        const card = removeBtn.closest('[data-vcard]');
        if (!thumbWrap || !card) return;

        const label = document.createElement('label');
        label.className = 'vrow-thumb-placeholder';
        label.innerHTML = '<span>IMG</span>';
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.dataset.vrowThumbInput = '';
        const idInput = card.querySelector('input[name$="[id]"]');
        const match = idInput?.name.match(/^(.*)\[id\]$/);
        input.name = match ? `${match[1]}[image]` : '';
        label.appendChild(input);
        thumbWrap.innerHTML = '';
        thumbWrap.appendChild(label);

        const flag = card.querySelector('[data-vrow-remove-flag]');
        if (flag) flag.value = '1';
    });
}
