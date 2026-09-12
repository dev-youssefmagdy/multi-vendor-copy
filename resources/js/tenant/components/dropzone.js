import { checkImageDimensionWarning } from './file.js';

function fileIconFor(type) {
    if (type.startsWith('video/')) return 'video';
    if (type === 'application/pdf') return 'pdf';
    return 'file';
}

function render(el) {
    const input = el.querySelector('.t-dropzone-input');
    const list = el.querySelector('[data-dropzone-files]');
    const expectedWidth = parseInt(el.dataset.expectW, 10) || null;
    const expectedHeight = parseInt(el.dataset.expectH, 10) || null;

    if (!input || !list) {
        return;
    }

    (list._objectUrls || []).forEach((u) => URL.revokeObjectURL(u));
    list._objectUrls = [];
    list.innerHTML = '';

    // Existing (already-uploaded) items.
    el.querySelectorAll('.t-dropzone-existing').forEach((existing) => {
        if (existing.dataset.removed === '1') {
            return;
        }

        const item = document.createElement('div');
        item.className = 'dropzone-file';
        item.dataset.existingId = existing.dataset.existingId;

        const type = existing.dataset.existingType || 'image';
        const thumb = type === 'image'
            ? `<img class="dropzone-file-thumb" src="${existing.dataset.existingUrl}" alt="">`
            : '';

        item.innerHTML = `
            <div class="dropzone-file-row">
                ${thumb}
                <div class="dropzone-file-meta">
                    <span class="dropzone-file-name">${existing.dataset.existingName || ''}</span>
                </div>
                <button type="button" class="dropzone-file-remove" aria-label="Remove">
                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        `;

        item.querySelector('.dropzone-file-remove')?.addEventListener('click', () => {
            existing.dataset.removed = '1';
            addRemovedId(el, existing.dataset.existingId);
            render(el);
        });

        list.appendChild(item);
    });

    // Newly picked files.
    Array.from(input.files || []).forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'dropzone-file';

        const isImage = file.type.startsWith('image/');
        let thumbHtml = '';
        if (isImage) {
            const url = URL.createObjectURL(file);
            list._objectUrls.push(url);
            thumbHtml = `<img class="dropzone-file-thumb" src="${url}" alt="">`;
        }

        item.innerHTML = `
            <div class="dropzone-file-row">
                ${thumbHtml}
                <div class="dropzone-file-meta">
                    <span class="dropzone-file-name">${file.name}</span>
                    <span class="dropzone-file-size">${Math.max(1, Math.round(file.size / 1024))} KB</span>
                </div>
                <button type="button" class="dropzone-file-remove" aria-label="Remove ${file.name}">
                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        `;

        item.querySelector('.dropzone-file-remove')?.addEventListener('click', () => {
            const dt = new DataTransfer();
            Array.from(input.files || []).forEach((f, i) => {
                if (i !== index) dt.items.add(f);
            });
            input.files = dt.files;
            el._filePool = Array.from(input.files);
            render(el);
        });

        list.appendChild(item);

        if (isImage && expectedWidth && expectedHeight) {
            checkImageDimensionWarning(file, expectedWidth, expectedHeight).then((warning) => {
                if (!warning) {
                    return;
                }
                const warningEl = document.createElement('p');
                warningEl.className = 'dropzone-file-warning';
                warningEl.textContent = warning;
                item.appendChild(warningEl);
            });
        }
    });
}

function addRemovedId(el, id) {
    const removeName = el.dataset.removeName;
    const form = el.closest('form');
    if (!form || !removeName) {
        return;
    }

    let hidden = form.querySelector(`input[type="hidden"][data-dropzone-remove="${CSS.escape(id)}"]`);
    if (!hidden) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = `${removeName}[]`;
        hidden.dataset.dropzoneRemove = id;
        hidden.value = id;
        el.appendChild(hidden);
    }
}

function updateOrderInput(el) {
    const orderInput = el.querySelector('[data-order-input]');
    if (!orderInput) {
        return;
    }
    const ids = Array.from(el.querySelectorAll('.t-dropzone-existing:not([data-removed="1"])')).map((e) => e.dataset.existingId);
    orderInput.value = ids.join(',');
}

export async function init(el) {
    const input = el.querySelector('.t-dropzone-input');

    if (!input) {
        return;
    }

    input.addEventListener('change', () => {
        if (input.multiple) {
            const pool = el._filePool || [];
            const existingKeys = new Set(pool.map((f) => `${f.name}-${f.size}-${f.lastModified}`));
            const dt = new DataTransfer();
            pool.forEach((f) => dt.items.add(f));
            Array.from(input.files).forEach((f) => {
                const key = `${f.name}-${f.size}-${f.lastModified}`;
                if (!existingKeys.has(key)) dt.items.add(f);
            });
            input.files = dt.files;
            el._filePool = Array.from(input.files);
        }
        render(el);
    });

    if (el.dataset.sortable === 'true') {
        const { default: Sortable } = await import('sortablejs');
        const list = el.querySelector('[data-dropzone-files]');
        if (list) {
            Sortable.create(list, {
                handle: '.dropzone-file',
                animation: 150,
                onEnd: () => updateOrderInput(el),
            });
        }
    }

    render(el);
}
