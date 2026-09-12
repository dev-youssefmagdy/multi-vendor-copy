function readImageDimensions(file) {
    return new Promise((resolve) => {
        if (!file.type || !file.type.startsWith('image/')) {
            resolve(null);
            return;
        }
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => {
            resolve({ width: img.naturalWidth, height: img.naturalHeight });
            URL.revokeObjectURL(url);
        };
        img.onerror = () => {
            resolve(null);
            URL.revokeObjectURL(url);
        };
        img.src = url;
    });
}

export async function checkImageDimensionWarning(file, expectedWidth, expectedHeight) {
    if (!expectedWidth || !expectedHeight) {
        return null;
    }
    const dims = await readImageDimensions(file);
    if (!dims) {
        return null;
    }
    if (dims.width === expectedWidth && dims.height === expectedHeight) {
        return null;
    }
    return `Image should be ${expectedWidth}×${expectedHeight}. Your image is ${dims.width}×${dims.height}.`;
}

export function init(el) {
    const input = el.querySelector('input[type="file"]');
    const warningEl = el.querySelector('.dimension-warning');
    const expectedWidth = parseInt(el.dataset.expectW, 10) || null;
    const expectedHeight = parseInt(el.dataset.expectH, 10) || null;

    if (!input || !warningEl) {
        return;
    }

    input.addEventListener('change', async () => {
        const file = input.files && input.files[0];
        warningEl.hidden = true;
        warningEl.textContent = '';

        if (!file) {
            return;
        }

        const warning = await checkImageDimensionWarning(file, expectedWidth, expectedHeight);
        if (warning) {
            warningEl.textContent = warning;
            warningEl.hidden = false;
        }
    });
}
