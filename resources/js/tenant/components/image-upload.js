import { checkImageDimensionWarning } from './file.js';

export function init(el) {
    const input = el.querySelector('input[type="file"]');
    const preview = el.querySelector('.t-image-upload-preview');
    const warningEl = el.querySelector('.dimension-warning');
    const expectedWidth = parseInt(el.dataset.expectW, 10) || null;
    const expectedHeight = parseInt(el.dataset.expectH, 10) || null;

    if (!input) {
        return;
    }

    input.addEventListener('change', async () => {
        const file = input.files && input.files[0];
        if (warningEl) {
            warningEl.hidden = true;
            warningEl.textContent = '';
        }

        if (!file) {
            return;
        }

        if (preview && file.type.startsWith('image/')) {
            if (preview.dataset.blobUrl) {
                URL.revokeObjectURL(preview.dataset.blobUrl);
            }
            const url = URL.createObjectURL(file);
            preview.dataset.blobUrl = url;
            preview.src = url;
            preview.hidden = false;
        }

        const warning = await checkImageDimensionWarning(file, expectedWidth, expectedHeight);
        if (warning && warningEl) {
            warningEl.textContent = warning;
            warningEl.hidden = false;
        }
    });
}
