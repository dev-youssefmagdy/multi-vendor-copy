// Price-list modal arithmetic + rendering.
//
// computeFinalPrice() is a direct, verified port of the PHP
// Product::computeFinalPrice() (app/Models/Tenant/Product.php): a simple
// percentage-or-fixed markup plus a flat shipping amount, rounded to 2
// decimals. There is no rounding/edge-case subtlety here (no currency
// exponent tables, no intermediate rounding steps) so it is safe to
// duplicate client-side for instant recalculation as profit inputs change.
// The server remains the source of truth on Save (PUT price-list), and a
// POST .../price-list/preview endpoint exists as an escape hatch if this
// ever needs to diverge from the PHP implementation.
export function computeFinalPrice(basePrice, profitType, profitValue, shipping) {
    const base = Number(basePrice) || 0;
    const value = Math.max(0, Number(profitValue) || 0);
    const ship = Number(shipping) || 0;
    const profitAmount = profitType === 'fixed' ? value : (base * value) / 100;

    return Math.round((base + profitAmount + ship) * 100) / 100;
}

export function computePrices(basePrice, profitRows, shippingByCountry) {
    const prices = {};
    Object.entries(profitRows || {}).forEach(([key, row]) => {
        const type = (row?.type || 'percentage') === 'fixed' ? 'fixed' : 'percentage';
        const value = Math.max(0, Number(row?.value) || 0);
        const shipping = Number((shippingByCountry || {})[key] ?? 0);
        prices[key] = computeFinalPrice(basePrice, type, value, shipping);
    });

    return prices;
}

function money(value) {
    return `$${Number(value || 0).toFixed(2)}`;
}

function countryLabel(labels, key) {
    return labels[key] || `Country #${key}`;
}

/**
 * Render the readonly product/variant price-list rows into a <table> body,
 * and wire the profit type/value inputs to recalculate "Your Price" live.
 */
export function renderPriceTable(tbody, { basePrice, profits, shippingByCountry, countryLabels }, onChange) {
    tbody.innerHTML = '';

    Object.keys(profits).forEach((key) => {
        const row = profits[key];
        const shipping = Number(shippingByCountry[key] ?? 0);
        const tr = document.createElement('tr');

        const yourPrice = computeFinalPrice(basePrice, row.type, row.value, shipping);

        tr.innerHTML = `
            <td>${countryLabel(countryLabels, key)}${key === 'default' ? ' <span class="badge badge-amber price-list-fallback-badge">Fallback</span>' : ''}</td>
            <td>${money(basePrice)}</td>
            <td>
                <div class="price-list-profit-cell">
                    <select class="field-control" data-profit-type>
                        <option value="percentage" ${row.type !== 'fixed' ? 'selected' : ''}>% of price</option>
                        <option value="fixed" ${row.type === 'fixed' ? 'selected' : ''}>Fixed ($)</option>
                    </select>
                    <input type="number" step="0.01" min="0" class="field-control" data-profit-value value="${row.value}">
                </div>
            </td>
            <td>${money(shipping)}</td>
            <td class="price-list-your-price" data-your-price>${money(yourPrice)}</td>
        `;

        const typeSelect = tr.querySelector('[data-profit-type]');
        const valueInput = tr.querySelector('[data-profit-value]');
        const priceCell = tr.querySelector('[data-your-price]');

        const recalc = () => {
            row.type = typeSelect.value;
            row.value = parseFloat(valueInput.value) || 0;
            priceCell.textContent = money(computeFinalPrice(basePrice, row.type, row.value, shipping));
            onChange?.();
        };

        typeSelect.addEventListener('change', recalc);
        valueInput.addEventListener('input', recalc);

        tbody.appendChild(tr);
    });
}
