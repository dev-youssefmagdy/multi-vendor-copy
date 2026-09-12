import loadFlatpickr from '../vendor/flatpickr.js';

export async function init(el) {
    const lang = document.documentElement.lang || 'en';
    const flatpickr = await loadFlatpickr(lang);

    const enableTime = el.dataset.enableTime === 'true';
    const noCalendar = el.dataset.noCalendar === 'true';

    el._flatpickr = flatpickr(el, {
        dateFormat: el.dataset.format || 'Y-m-d',
        altInput: !noCalendar,
        altFormat: el.dataset.altFormat || 'M d, Y',
        enableTime,
        noCalendar,
        time_24hr: false,
        minDate: el.dataset.min || null,
        maxDate: el.dataset.max || null,
        locale: lang.startsWith('ar') ? 'ar' : undefined,
        onChange() {
            el.dispatchEvent(new Event('change', { bubbles: true }));
        },
    });
}

export function destroy(el) {
    el._flatpickr?.destroy();
}
