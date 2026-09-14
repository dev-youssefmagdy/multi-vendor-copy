import { emit } from '../core/events.js';

function openModal(modal) {
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
}

function closeModal(modal) {
    modal.hidden = true;
    document.body.style.overflow = '';
    stopCamera(modal);
    resetModal(modal);
}

function resetModal(modal) {
    modal.querySelector('[data-ism-choice]').hidden = false;
    modal.querySelector('[data-ism-camera]').hidden = true;
    modal.querySelector('[data-ism-preview]').hidden = true;
    modal.querySelector('[data-ism-loading]').hidden = true;
    modal.querySelector('[data-ism-error]').hidden = true;
    modal.querySelector('[data-ism-file-input]').value = '';
    modal.__blob = null;
}

function showError(modal, message) {
    const el = modal.querySelector('[data-ism-error]');
    el.textContent = message;
    el.hidden = false;
}

function stopCamera(modal) {
    if (modal.__stream) {
        modal.__stream.getTracks().forEach((t) => t.stop());
        modal.__stream = null;
    }
}

function showPreview(modal, blob) {
    modal.__blob = blob;
    modal.querySelector('[data-ism-choice]').hidden = true;
    modal.querySelector('[data-ism-camera]').hidden = true;
    const img = modal.querySelector('[data-ism-preview-img]');
    img.src = URL.createObjectURL(blob);
    modal.querySelector('[data-ism-preview]').hidden = false;
}

async function startCamera(modal) {
    modal.querySelector('[data-ism-choice]').hidden = true;
    modal.querySelector('[data-ism-camera]').hidden = false;
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        modal.__stream = stream;
        modal.querySelector('[data-ism-video]').srcObject = stream;
    } catch {
        showError(modal, 'Could not access the camera. Please allow camera permission or use Upload Image instead.');
        modal.querySelector('[data-ism-camera]').hidden = true;
        modal.querySelector('[data-ism-choice]').hidden = false;
    }
}

function capturePhoto(modal) {
    const video = modal.querySelector('[data-ism-video]');
    const canvas = modal.querySelector('[data-ism-canvas]');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    stopCamera(modal);
    canvas.toBlob((blob) => {
        if (blob) {
            showPreview(modal, blob);
        } else {
            showError(modal, 'Could not capture the photo. Please try again.');
        }
    }, 'image/jpeg', 0.92);
}

async function submitSearch(modal) {
    if (!modal.__blob) {
        return;
    }

    modal.querySelector('[data-ism-preview]').hidden = true;
    modal.querySelector('[data-ism-error]').hidden = true;
    modal.querySelector('[data-ism-loading]').hidden = false;

    const formData = new FormData();
    formData.append('image', modal.__blob, 'query-image.jpg');
    const token = document.querySelector('meta[name="csrf-token"]');

    try {
        const response = await fetch(modal.dataset.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'X-CSRF-TOKEN': token.content } : {}),
            },
        });

        const contentType = response.headers.get('content-type') || '';
        if (!response.ok || !contentType.includes('application/json')) {
            throw new Error('Image search failed. Please try again.');
        }

        const json = await response.json();
        closeModal(modal);

        const ids = Array.isArray(json.ids) ? json.ids : (json.product_ids || []);
        emit('tenant:image-search:results', { ids });
    } catch (e) {
        modal.querySelector('[data-ism-loading]').hidden = true;
        showError(modal, e.message || 'Image search failed. Please try again.');
        modal.querySelector('[data-ism-preview]').hidden = false;
    }
}

export function init(modal) {
    if (modal.__ismInit) {
        return;
    }
    modal.__ismInit = true;

    modal.querySelector('[data-ism-close]').addEventListener('click', () => closeModal(modal));
    modal.querySelector('[data-ism-backdrop]').addEventListener('click', () => closeModal(modal));

    modal.querySelector('[data-ism-take-photo]').addEventListener('click', () => startCamera(modal));
    modal.querySelector('[data-ism-cancel-camera]').addEventListener('click', () => {
        stopCamera(modal);
        modal.querySelector('[data-ism-camera]').hidden = true;
        modal.querySelector('[data-ism-choice]').hidden = false;
    });
    modal.querySelector('[data-ism-capture]').addEventListener('click', () => capturePhoto(modal));

    const fileInput = modal.querySelector('[data-ism-file-input]');
    modal.querySelector('[data-ism-upload]').addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => {
        if (fileInput.files && fileInput.files[0]) {
            showPreview(modal, fileInput.files[0]);
        }
    });

    modal.querySelector('[data-ism-retake]').addEventListener('click', () => resetModal(modal));
    modal.querySelector('[data-ism-submit]').addEventListener('click', () => submitSearch(modal));

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-image-search-open]');
        if (trigger && document.getElementById(trigger.dataset.imageSearchOpen) === modal) {
            openModal(modal);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal(modal);
        }
    });
}
