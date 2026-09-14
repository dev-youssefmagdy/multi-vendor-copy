function slugify(value) {
    return String(value ?? '')
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\p{L}\p{N}-]+/gu, '')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

export function init(el) {
    const sourceName = el.dataset.slugFrom;
    if (!sourceName) {
        return;
    }

    const form = el.closest('form');
    const source = form?.querySelector(`[name="${CSS.escape(sourceName)}"]`);
    if (!source) {
        return;
    }

    let userEdited = el.value.trim() !== '';

    el.addEventListener('input', () => {
        userEdited = true;
    });

    source.addEventListener('input', () => {
        if (!userEdited) {
            el.value = slugify(source.value);
            el.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });
}
