import { get } from '@tenant/core/http.js';

const form = document.getElementById('product-form');
const select = form?.querySelector('[data-central-product-select]');
const snapshotUrlTemplate = form?.dataset.centralSnapshotUrlTemplate;
const panels = document.querySelector('[data-central-product-panels]');
const snapshotBody = document.querySelector('[data-central-snapshot-body]');
const variantsBody = document.querySelector('[data-variants-body]');
const currentPriceEl = document.querySelector('[data-central-current-price]');
const slugInput = form?.querySelector('[name="slug"]');
const priceInput = form?.querySelector('[name="price"]');
const productIdMatch = form?.action.match(/products\/(\d+)/);

async function loadSnapshot(centralProductId) {
    if (!centralProductId || !snapshotUrlTemplate) {
        panels?.setAttribute('hidden', '');
        return;
    }

    const url = snapshotUrlTemplate.replace('__ID__', centralProductId) + (productIdMatch ? `?product_id=${productIdMatch[1]}` : '');

    try {
        const data = await get(url, {}, { toast: false });

        if (snapshotBody) snapshotBody.innerHTML = data.snapshot_html;
        if (variantsBody) variantsBody.innerHTML = data.variants_html;
        panels?.removeAttribute('hidden');

        if (currentPriceEl) {
            currentPriceEl.textContent = `Central current price: $${Number(data.price).toFixed(2)}`;
            currentPriceEl.hidden = false;
        }

        if (slugInput && !slugInput.value) {
            slugInput.value = data.slug;
        }
        if (priceInput && parseFloat(priceInput.value) <= 0) {
            priceInput.value = data.price;
        }
    } catch {
        panels?.setAttribute('hidden', '');
    }
}

if (select) {
    select.addEventListener('change', () => loadSnapshot(select.value));
}
