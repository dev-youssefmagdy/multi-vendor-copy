import '@tenant-css/pages/badges.css';
import { post } from '@tenant/core/http.js';
import { confirm } from '@tenant/core/confirm.js';

function jq() {
    return window.jQuery;
}

const categoryAssign = document.querySelector('[data-badge-category-assign]');
const categorySelect = categoryAssign?.querySelector('select[name="category_id"]');
const assignBtn = categoryAssign?.querySelector('[data-assign-category-btn]');
const assignMsg = document.querySelector('[data-assign-category-msg]');
const productPicker = document.querySelector('[data-badge-product-picker]');
const saveBtn = document.querySelector('[data-save-assignment]');

if (categorySelect && assignBtn) {
    const $ = jq();
    const toggle = () => {
        assignBtn.hidden = !categorySelect.value;
        if (assignMsg) assignMsg.hidden = true;
    };

    if ($?.fn.select2) {
        $(categorySelect).on('change', toggle);
    } else {
        categorySelect.addEventListener('change', toggle);
    }

    assignBtn.addEventListener('click', async () => {
        if (!categorySelect.value) return;

        const ok = await confirm({ text: 'Merge ALL products in the selected category into the current selection?' });
        if (!ok) return;

        assignBtn.disabled = true;
        assignBtn.textContent = 'Merging…';

        try {
            const data = await post(assignBtn.dataset.assignUrl, { category_id: parseInt(categorySelect.value, 10) }, { toast: false });

            const $picker = jq()?.(productPicker);
            Object.entries(data.productLabels || {}).forEach(([id, text]) => {
                if ($picker && !$picker.find(`option[value="${id}"]`).length) {
                    $picker.append(new Option(text, id, true, true));
                } else if ($picker) {
                    const option = $picker.find(`option[value="${id}"]`);
                    option.prop('selected', true);
                }
            });
            $picker?.trigger('change');

            if (assignMsg) {
                assignMsg.textContent = data.message;
                assignMsg.hidden = false;
            }
        } catch (error) {
            if (assignMsg) {
                assignMsg.textContent = error.message || 'Error';
                assignMsg.hidden = false;
            }
        } finally {
            assignBtn.disabled = false;
            assignBtn.textContent = 'Merge all in category';
        }
    });
}

saveBtn?.addEventListener('click', async () => {
    const productIds = productPicker ? Array.from(productPicker.selectedOptions).map((o) => o.value) : [];
    const countryId = saveBtn.dataset.countryId || null;

    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving…';

    try {
        await post(saveBtn.dataset.actionUrl, { product_ids: productIds, country_id: countryId });
    } catch {
        // toast already shown globally
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save assignment';
    }
});
