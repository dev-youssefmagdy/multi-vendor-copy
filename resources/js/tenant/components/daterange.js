import loadFlatpickr from '../vendor/flatpickr.js';

export async function init(el) {
    const lang = document.documentElement.lang || 'en';
    const flatpickr = await loadFlatpickr(lang);

    const visible = el.querySelector('input[type="text"]');
    const startInput = el.querySelector(`input[name="${CSS.escape(el.dataset.startName)}"]`);
    const endInput = el.querySelector(`input[name="${CSS.escape(el.dataset.endName)}"]`);

    if (!visible || !startInput || !endInput) {
        return;
    }

    const defaultDate = [startInput.value, endInput.value].filter(Boolean);

    el._flatpickr = flatpickr(visible, {
        mode: 'range',
        enableTime: el.dataset.enableTime === 'true',
        dateFormat: 'Y-m-d',
        defaultDate: defaultDate.length === 2 ? defaultDate : undefined,
        onChange(selectedDates) {
            if (selectedDates.length === 2) {
                const [start, end] = selectedDates;
                startInput.value = start.toISOString().slice(0, 10);
                endInput.value = end.toISOString().slice(0, 10);
                startInput.dispatchEvent(new Event('change', { bubbles: true }));
                endInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        },
    });
}

export function destroy(el) {
    el._flatpickr?.destroy();
}
