export function init(el) {
    const picker = el.querySelector('[data-color-picker]');
    const text = el.querySelector('[data-color-text]');
    const transparentToggle = el.querySelector('[data-color-transparent]');

    if (!picker || !text) {
        return;
    }

    picker.addEventListener('input', () => {
        text.value = picker.value;
        text.dispatchEvent(new Event('change', { bubbles: true }));
        if (transparentToggle) {
            transparentToggle.checked = false;
        }
    });

    text.addEventListener('change', () => {
        if (/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(text.value)) {
            picker.value = text.value;
        }
    });

    transparentToggle?.addEventListener('change', () => {
        if (transparentToggle.checked) {
            text.value = 'transparent';
            text.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    el.querySelectorAll('[data-swatch]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const value = btn.dataset.swatch;
            text.value = value;
            picker.value = value;
            if (transparentToggle) {
                transparentToggle.checked = false;
            }
            text.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
}
