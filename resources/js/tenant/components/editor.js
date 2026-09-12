import loadTinymce from '../vendor/tinymce.js';
import { on } from '../core/events.js';

const FULL_TOOLBAR = 'undo redo | styles | fontfamily fontsize | bold italic underline strikethrough | link image media table | bullist numlist | code fullscreen';
const BASIC_TOOLBAR = 'undo redo | bold italic underline | bullist numlist | link';

function themeConfig() {
    const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
    return {
        skin: isDark ? 'oxide-dark' : 'oxide',
        content_css: isDark ? 'dark' : 'default',
    };
}

export async function init(el) {
    const tinymce = await loadTinymce();

    if (!el.id) {
        el.id = `tme-${Math.random().toString(36).slice(2, 10)}`;
    }

    const height = parseInt(el.dataset.height || '400', 10);
    const toolbar = el.dataset.toolbar === 'basic' ? BASIC_TOOLBAR : FULL_TOOLBAR;
    const dir = el.getAttribute('dir') || document.documentElement.dir || 'ltr';

    async function initEditor() {
        const { skin, content_css: contentCss } = themeConfig();

        await tinymce.init({
            selector: `#${el.id}`,
            height,
            menubar: 'file edit view insert format tools table',
            plugins: 'lists link image media table wordcount code fullscreen',
            toolbar,
            branding: false,
            promotion: false,
            license_key: 'gpl',
            skin,
            content_css: contentCss,
            directionality: dir,
            placeholder: el.dataset.placeholder || undefined,
            setup(editor) {
                editor.on('change input', () => {
                    editor.save();
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                });
            },
        });
    }

    await initEditor();

    el._tenantBeforeSubmit = () => {
        tinymce.get(el.id)?.save();
    };
    el.dataset.tenantReady = '1';

    const off = on('tenant:theme-changed', async () => {
        tinymce.get(el.id)?.remove();
        await initEditor();
    });

    el._tenantThemeOff = off;
}

export function destroy(el) {
    import('../vendor/tinymce.js').then(({ default: loadTinymce }) =>
        loadTinymce().then((tinymce) => tinymce.get(el.id)?.remove()),
    );
    el._tenantThemeOff?.();
}
