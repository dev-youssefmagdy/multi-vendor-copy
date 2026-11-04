export default async function loadFlatpickr(lang = 'en') {
    // ESM entry points on purpose: the UMD builds get mis-bundled by Rolldown
    // (the chunk ends up exporting an undefined `flatpickr_min_exports`).
    const { default: flatpickr } = await import('flatpickr/dist/esm/index.js');
    await import('flatpickr/dist/flatpickr.css');

    if (String(lang).startsWith('ar')) {
        const { Arabic } = await import('flatpickr/dist/esm/l10n/ar.js');
        flatpickr.l10ns.ar = Arabic;
    }

    return flatpickr;
}
