export default async function loadFlatpickr() {
    const { default: flatpickr } = await import('flatpickr');
    await import('flatpickr/dist/flatpickr.min.css');
    return flatpickr;
}
