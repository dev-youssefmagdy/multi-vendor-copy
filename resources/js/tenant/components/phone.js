const instances = new WeakMap();

export async function init(el) {
    const visible = el.querySelector('.t-phone-visible');
    const hidden = el.querySelector('[data-phone-e164]');

    if (!visible || !hidden) {
        return;
    }

    const [{ default: intlTelInput }, { default: intlTelInputUtils }] = await Promise.all([
        import('intl-tel-input'),
        import('intl-tel-input/utils'),
    ]);
    await import('intl-tel-input/styles');

    const preferred = (el.dataset.preferred || '').split(',').filter(Boolean);

    const iti = intlTelInput(visible, {
        initialCountry: el.dataset.initialCountry || 'sa',
        separateDialCode: true,
        dropdownParent: document.body,
        preferredCountries: preferred.length ? preferred : undefined,
        loadUtils: () => Promise.resolve({ default: intlTelInputUtils }),
    });

    instances.set(el, iti);

    if (hidden.value) {
        try {
            visible.value = hidden.value;
            iti.setNumber(hidden.value);
        } catch {
            /* ignore malformed stored number */
        }
    }

    const sync = () => {
        const full = iti.getNumber();
        if (full) {
            hidden.value = full;
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };

    visible.addEventListener('blur', sync);
    visible.addEventListener('countrychange', sync);

    el._tenantBeforeSubmit = sync;
}

export function destroy(el) {
    instances.get(el)?.destroy();
    instances.delete(el);
}
