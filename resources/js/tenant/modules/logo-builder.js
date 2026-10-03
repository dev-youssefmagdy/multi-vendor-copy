// Shared logo builder — powers the live preview in `_logo-builder.blade.php`,
// included from Store ▸ Appearance (General tab) and, eventually, Onboarding.
// Ports the old Livewire `logo-builder.blade.php` inline <script> 1:1:
// text/image mode switch, font preview, colour/background/shape preview,
// and the transparent-background toggle. Image upload preview itself is
// handled by the generic `x-tenant::image-upload` component.
//
// Supports a configurable field prefix via data-logo-builder="prefix_" so the
// footer logo builder (prefix "footer_logo_") can reuse the same logic.
import '@tenant-css/pages/logo-fonts.css';

function applyPreviewStyle(root, prefix) {
    const colorField = root.querySelector(`[name="${prefix}color"]`);
    const bgField = root.querySelector(`[name="${prefix}bg_color"]`);
    const shapeField = root.querySelector(`[name="${prefix}shape"]`);

    const color = colorField?.value || '#111827';
    const bgColor = bgField?.value || '#ffffff';
    const radius = shapeField?.value === 'rounded' ? '999px' : '10px';

    root.querySelectorAll('[data-logo-preview]').forEach((preview) => {
        preview.style.color = color;
        preview.style.backgroundColor = bgColor === 'transparent' ? 'transparent' : bgColor;
        preview.style.borderRadius = radius;
    });
}

function bindTextPreview(root) {
    ['ar', 'en'].forEach((locale) => {
        const textInput = root.querySelector(`[data-logo-text="${locale}"]`);
        const preview = root.querySelector(`[data-logo-preview="${locale}"]`);
        const fontSelect = root.querySelector(`[data-logo-font="${locale}"]`);
        const placeholder = locale === 'ar' ? 'اسم المتجر' : 'Store Name';

        const syncText = () => {
            if (preview) {
                preview.textContent = textInput?.value ? textInput.value : placeholder;
            }
        };

        const syncFont = () => {
            const option = fontSelect?.options[fontSelect.selectedIndex];
            const family = option?.dataset.fontFamily;
            if (preview && family) {
                preview.style.fontFamily = family;
            }
        };

        textInput?.addEventListener('input', syncText);
        fontSelect?.addEventListener('change', syncFont);

        syncText();
        syncFont();
    });
}

function bindColorPreview(root, prefix) {
    const colorField = root.querySelector(`[name="${prefix}color"]`);
    const bgField = root.querySelector(`[name="${prefix}bg_color"]`);
    const shapeField = root.querySelector(`[name="${prefix}shape"]`);

    const refresh = () => applyPreviewStyle(root, prefix);

    colorField?.addEventListener('change', refresh);
    bgField?.addEventListener('change', refresh);
    shapeField?.addEventListener('change', refresh);

    refresh();
}

function bindModeSwitch(root) {
    const modeInput = root.querySelector('[data-logo-mode-input]');
    const buttons = root.querySelectorAll('[data-logo-mode-btn]');

    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.logoModeBtn;
            if (modeInput) {
                modeInput.value = mode;
            }

            buttons.forEach((b) => b.classList.toggle('act', b === btn));

            root.querySelectorAll('[data-logo-mode-panel]').forEach((panel) => {
                panel.hidden = panel.dataset.logoModePanel !== mode;
            });
        });
    });
}

function initLogoBuilder(root) {
    if (root.dataset.logoBuilderBound) {
        return;
    }
    root.dataset.logoBuilderBound = '1';

    // data-logo-builder="" or data-logo-builder="logo_" → prefix "logo_"
    // data-logo-builder="footer_logo_"                   → prefix "footer_logo_"
    const raw = root.dataset.logoBuilder || '';
    const prefix = raw || 'logo_';

    bindModeSwitch(root);
    bindTextPreview(root);
    bindColorPreview(root, prefix);
}

document.querySelectorAll('[data-logo-builder]').forEach(initLogoBuilder);

// Included forms can be re-rendered inside a modal (Onboarding); observe for
// late-arriving instances instead of requiring a page reload.
const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (!(node instanceof HTMLElement)) {
                return;
            }
            if (node.matches?.('[data-logo-builder]')) {
                initLogoBuilder(node);
            }
            node.querySelectorAll?.('[data-logo-builder]').forEach(initLogoBuilder);
        });
    });
});

observer.observe(document.body, { childList: true, subtree: true });
