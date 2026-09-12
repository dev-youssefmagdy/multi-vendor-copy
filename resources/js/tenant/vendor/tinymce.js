export default async function loadTinymce() {
    const { default: tinymce } = await import('tinymce/tinymce');

    await import('tinymce/models/dom/model');
    await import('tinymce/themes/silver');
    await import('tinymce/icons/default');

    await Promise.all([
        import('tinymce/plugins/lists'),
        import('tinymce/plugins/link'),
        import('tinymce/plugins/image'),
        import('tinymce/plugins/media'),
        import('tinymce/plugins/table'),
        import('tinymce/plugins/wordcount'),
        import('tinymce/plugins/code'),
        import('tinymce/plugins/fullscreen'),
    ]);

    await Promise.all([
        import('tinymce/skins/ui/oxide/skin.js'),
        import('tinymce/skins/ui/oxide-dark/skin.js'),
        import('tinymce/skins/content/default/content.js'),
        import('tinymce/skins/content/dark/content.js'),
    ]);

    return tinymce;
}
