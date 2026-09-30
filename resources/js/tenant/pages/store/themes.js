import '../../../../css/tenant/pages/themes.css';
import { get, put } from '../../core/http.js';
import { openModal, closeModal } from '../../core/modals.js';

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

// ── Country Variants modal ───────────────────────────────────────────────────

let _cvActionUrl = '';

function renderCvRows(countries, variants) {
    if (!countries.length) {
        return '<p class="theme-country-empty">No countries are configured for this theme.</p>';
    }

    const defaultOption = '<option value="">Default</option>';
    const variantOptions = variants
        .map((v) => `<option value="${v.id}">${escapeHtml(v.name)}</option>`)
        .join('');

    return countries
        .map((c) => {
            const selected = c.home_variant_id ? ` data-selected="${c.home_variant_id}"` : '';
            return `
            <div class="theme-country-row" data-cv-row
                data-country-id="${c.country_id}"
                data-country-name="${escapeHtml(c.name)}"
                data-country-iso="${escapeHtml(c.iso2 || '')}">
                <span class="theme-country-flag">${escapeHtml(c.flag_emoji || '🏳️')}</span>
                <span class="theme-country-name">${escapeHtml(c.name)}</span>
                <span class="theme-country-iso">${escapeHtml(c.iso2 || '')}</span>
                <select class="theme-cv-select" data-cv-select${selected}>
                    ${defaultOption}${variantOptions}
                </select>
            </div>`;
        })
        .join('');
}

function hydrateCvSelects(listEl) {
    listEl.querySelectorAll('[data-cv-select]').forEach((sel) => {
        const target = sel.dataset.selected;
        if (target) {
            sel.value = target;
        }
    });
}

async function openCvModal(trigger) {
    const url = trigger.dataset.cvUrl;
    _cvActionUrl = trigger.dataset.cvAction || '';
    const themeName = trigger.dataset.themeName || '';

    let response;
    try {
        response = await get(url, {}, { toast: false });
    } catch {
        return;
    }

    const modal = document.getElementById('theme-country-variants-modal');
    if (!modal) return;

    const nameEl = modal.querySelector('[data-cv-theme-name]');
    if (nameEl) nameEl.textContent = themeName || response.data?.theme_name || '';

    const listEl = modal.querySelector('[data-cv-list]');
    if (listEl) {
        listEl.innerHTML = renderCvRows(
            response.data?.countries ?? [],
            response.data?.variants ?? [],
        );
        hydrateCvSelects(listEl);
    }

    const searchInput = modal.querySelector('[data-cv-search]');
    if (searchInput) searchInput.value = '';

    await openModal('theme-country-variants-modal');
}

async function saveCvAssignments() {
    const modal = document.getElementById('theme-country-variants-modal');
    if (!modal || !_cvActionUrl) return;

    const assignments = [];
    modal.querySelectorAll('[data-cv-row]').forEach((row) => {
        const countryId = parseInt(row.dataset.countryId, 10);
        const sel = row.querySelector('[data-cv-select]');
        const variantId = sel ? (sel.value ? parseInt(sel.value, 10) : null) : null;
        assignments.push({ country_id: countryId, home_variant_id: variantId });
    });

    try {
        await put(_cvActionUrl, { assignments });
        closeModal('theme-country-variants-modal');
    } catch {
        // error toast is handled by the http module
    }
}

document.addEventListener('click', (event) => {
    const cvTrigger = event.target.closest('[data-theme-cv-open]');
    if (cvTrigger) {
        event.preventDefault();
        openCvModal(cvTrigger);
        return;
    }

    const cvSave = event.target.closest('[data-cv-save]');
    if (cvSave) {
        event.preventDefault();
        saveCvAssignments();
    }
});

document.addEventListener('input', (event) => {
    const search = event.target.closest('[data-cv-search]');
    if (!search) return;

    const modal = search.closest('[data-tenant-modal]');
    const term = search.value.trim().toLowerCase();

    modal?.querySelectorAll('[data-cv-row]').forEach((row) => {
        const name = (row.dataset.countryName || '').toLowerCase();
        const iso = (row.dataset.countryIso || '').toLowerCase();
        row.hidden = term !== '' && !name.includes(term) && !iso.includes(term);
    });
});
