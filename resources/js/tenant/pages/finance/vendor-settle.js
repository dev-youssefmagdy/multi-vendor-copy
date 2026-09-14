import '@tenant-css/pages/finance.css';
import { get } from '@tenant/core/http.js';

const root = document.querySelector('[data-vendor-settle]');
const breakdownBody = document.querySelector('[data-breakdown-body]');
const gatewaySelect = root?.querySelector('[data-payment-gateway-select]');

async function refreshBreakdown(gatewayCode) {
    const urlTemplate = root?.dataset.breakdownUrl;
    if (!urlTemplate || !breakdownBody) {
        return;
    }

    const url = `${urlTemplate}${gatewayCode ? `?gateway=${encodeURIComponent(gatewayCode)}` : ''}`;

    try {
        const data = await get(url, {}, { toast: false });
        breakdownBody.innerHTML = data.breakdown_html;
    } catch {
        // Keep the previously rendered breakdown on failure.
    }
}

if (gatewaySelect) {
    gatewaySelect.addEventListener('change', (event) => {
        const input = event.target.closest('input[type="radio"]');
        if (input) {
            refreshBreakdown(input.value);
        }
    });
}
