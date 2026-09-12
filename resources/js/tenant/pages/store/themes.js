import '../../../../css/tenant/pages/themes.css';
import { get } from '../../core/http.js';
import { openModal } from '../../core/modals.js';

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    }[ch]));
}

function renderCountryRows(countries) {
    if (!countries.length) {
        return '<p class="theme-country-empty">No countries are configured for this theme.</p>';
    }

    return countries.map((country) => `
        <label class="toggle-field theme-country-row" data-country-row data-country-name="${escapeHtml(country.name)}" data-country-iso="${escapeHtml(country.iso2)}">
            <input type="checkbox" name="country_ids[]" value="${country.country_id}" ${country.enabled ? 'checked' : ''}>
            <span class="theme-country-flag">${escapeHtml(country.flag_emoji || '🏳️')}</span>
            <span class="theme-country-name">${escapeHtml(country.name)}</span>
            <span class="theme-country-iso">${escapeHtml(country.iso2)}</span>
        </label>
    `).join('');
}

async function openCountriesModal(trigger) {
    const url = trigger.dataset.countriesUrl;
    const actionUrl = trigger.dataset.countriesAction;
    const themeName = trigger.dataset.themeName || '';

    let response;
    try {
        response = await get(url, {}, { toast: false });
    } catch {
        return;
    }

    const modal = document.getElementById('theme-countries-modal');
    if (!modal) {
        return;
    }

    const nameEl = modal.querySelector('[data-countries-theme-name]');
    if (nameEl) {
        nameEl.textContent = themeName || response.data?.theme_name || '';
    }

    const groupEl = modal.querySelector('[data-checkbox-group]');
    if (groupEl) {
        const hidden = groupEl.querySelector('input[type="hidden"]');
        groupEl.innerHTML = '';
        if (hidden) {
            groupEl.appendChild(hidden);
        } else {
            groupEl.insertAdjacentHTML('beforeend', '<input type="hidden" name="country_ids" value="">');
        }
        groupEl.insertAdjacentHTML('beforeend', renderCountryRows(response.data?.countries ?? []));
        groupEl.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const searchInput = modal.querySelector('[data-countries-search]');
    if (searchInput) {
        searchInput.value = '';
    }

    await openModal('theme-countries-modal', { action: actionUrl, mode: 'PUT' });
}

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-theme-countries-open]');
    if (trigger) {
        event.preventDefault();
        openCountriesModal(trigger);
    }
});

document.addEventListener('input', (event) => {
    const search = event.target.closest('[data-countries-search]');
    if (!search) {
        return;
    }

    const modal = search.closest('[data-tenant-modal]');
    const term = search.value.trim().toLowerCase();

    modal?.querySelectorAll('[data-country-row]').forEach((row) => {
        const name = (row.dataset.countryName || '').toLowerCase();
        const iso = (row.dataset.countryIso || '').toLowerCase();
        row.hidden = term !== '' && !name.includes(term) && !iso.includes(term);
    });
});
