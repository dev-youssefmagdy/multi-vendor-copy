export default async function loadFlatpickr(lang = 'en') {
    const { default: flatpickr } = await import('flatpickr');
    await import('flatpickr/dist/flatpickr.min.css');

    if (String(lang).startsWith('ar')) {
        const { Arabic } = await import('flatpickr/dist/l10n/ar.js');
        flatpickr.l10ns.ar = Arabic;
    }

    return flatpickr;
}
