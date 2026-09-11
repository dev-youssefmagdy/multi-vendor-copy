export default async function loadSelect2() {
    const { default: $ } = await import('./jquery.js');
    await import('select2/dist/js/select2.full.js');
    await import('select2/dist/css/select2.min.css');
    return $;
}
