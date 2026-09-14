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
