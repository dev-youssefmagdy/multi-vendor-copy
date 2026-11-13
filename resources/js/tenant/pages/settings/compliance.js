// Compliance center — five independent x-tenant::form sections plus a
// country → city cascade that repopulates the city select2 (the endpoint is
// path-based, `cities-by-country/{countryId}`, so it is wired here rather
// than through select2's generic `data-depends-on` ajax mode, which only
// supports a query-string dependency value).
import '@tenant-css/pages/compliance.css';
import loadSelect2 from '../../vendor/select2.js';

async function initCountryCityCascade() {
    const countrySelect = document.querySelector('[name="countryId"][data-tenant-select2]');
    const citySelect = document.querySelector('[name="cityId"][data-tenant-select2]');

    if (!countrySelect || !citySelect) {
        return;
    }

    const $ = await loadSelect2();
    const baseUrl = citySelect.dataset.citiesUrl;

    if (!baseUrl) {
        return;
    }

    countrySelect.addEventListener('change', async () => {
        const countryId = countrySelect.value;

        $(citySelect).empty();
        citySelect.disabled = true;

        if (countryId) {
            try {
                const response = await fetch(baseUrl.replace('__ID__', countryId) + '?format=select2');
                const payload = await response.json();

                (payload.results || []).forEach((city) => {
                    const option = new Option(city.text, city.id, false, false);
                    citySelect.appendChild(option);
                });

                citySelect.disabled = (payload.results || []).length === 0;
            } catch {
                citySelect.disabled = false;
            }
        }

        $(citySelect).val(null).trigger('change');
    });
}

// Business Name only applies to company accounts: hide it (and drop `required`,
// so the hidden field can't block the form) when Account Type is Individual.
function initAccountTypeToggle() {
    const accountType = document.querySelector('[name="accountType"]');
    const companyOnly = document.querySelectorAll('[data-company-only]');

    if (!accountType || companyOnly.length === 0) {
        return;
    }

    const sync = () => {
        const isCompany = accountType.value === 'company';

        companyOnly.forEach((wrap) => {
            wrap.hidden = !isCompany;
            wrap.querySelectorAll('input, select, textarea').forEach((field) => {
                field.required = isCompany;
            });
        });
    };

    accountType.addEventListener('change', sync);
    sync();
}

document.addEventListener('DOMContentLoaded', () => {
    initCountryCityCascade();
    initAccountTypeToggle();
});
