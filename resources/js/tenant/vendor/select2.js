export default async function loadSelect2() {
    const { default: $ } = await import('./jquery.js');
    if (!$.fn.select2) {
        const { default: initSelect2 } = await import('select2/dist/js/select2.full.js');
        initSelect2(window, $);
    }
    await import('select2/dist/css/select2.min.css');
    return $;
}
