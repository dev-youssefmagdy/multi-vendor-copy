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

document.addEventListener('DOMContentLoaded', () => {
    initCountryCityCascade();
});
