/**
 * Image search — camera / upload modal controller.
 * Storefront: follows redirect. Admin/Tenant panels: calls window[onResults](json).
 */
(function () {
    'use strict';

    function openModal(modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        stopCamera(modal);
        resetModal(modal);
    }

    function resetModal(modal) {
        modal.querySelector('.image-search-choice').classList.remove('hidden');
        modal.querySelector('.image-search-camera').classList.add('hidden');
        modal.querySelector('.image-search-preview').classList.add('hidden');
        modal.querySelector('.image-search-loading').classList.add('hidden');
        modal.querySelector('.image-search-error').classList.add('hidden');
        modal.querySelector('.image-search-file-input').value = '';
        modal.__blob = null;
    }

    function showError(modal, message) {
        var el = modal.querySelector('.image-search-error');
        el.textContent = message;
        el.classList.remove('hidden');
    }

    function stopCamera(modal) {
        if (modal.__stream) {
            modal.__stream.getTracks().forEach(function (t) { t.stop(); });
            modal.__stream = null;
        }
    }

    function showPreview(modal, blob) {
        modal.__blob = blob;
        modal.querySelector('.image-search-choice').classList.add('hidden');
        modal.querySelector('.image-search-camera').classList.add('hidden');
        var img = modal.querySelector('.image-search-preview-img');
        img.src = URL.createObjectURL(blob);
        modal.querySelector('.image-search-preview').classList.remove('hidden');
    }

    async function startCamera(modal) {
        modal.querySelector('.image-search-choice').classList.add('hidden');
        modal.querySelector('.image-search-camera').classList.remove('hidden');
        try {
            var stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            modal.__stream = stream;
            modal.querySelector('.image-search-video').srcObject = stream;
        } catch (e) {
            showError(modal, 'Could not access the camera. Please allow camera permission or use Upload Image instead.');
            modal.querySelector('.image-search-camera').classList.add('hidden');
            modal.querySelector('.image-search-choice').classList.remove('hidden');
        }
    }

    function capturePhoto(modal) {
        var video  = modal.querySelector('.image-search-video');
        var canvas = modal.querySelector('.image-search-canvas');
        canvas.width  = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        stopCamera(modal);
        canvas.toBlob(function (blob) {
            if (blob) { showPreview(modal, blob); }
            else { showError(modal, 'Could not capture the photo. Please try again.'); }
        }, 'image/jpeg', 0.92);
    }

    async function submitSearch(modal) {
        if (!modal.__blob) return;
        modal.querySelector('.image-search-preview').classList.add('hidden');
        modal.querySelector('.image-search-error').classList.add('hidden');
        modal.querySelector('.image-search-loading').classList.remove('hidden');

        var formData = new FormData();
        formData.append('image', modal.__blob, 'query-image.jpg');
        var token = document.querySelector('meta[name="csrf-token"]');

        try {
            var response = await fetch(modal.dataset.action, {
                method: 'POST',
                body: formData,
                headers: token ? { 'X-CSRF-TOKEN': token.content } : {},
                redirect: 'follow',
            });

            var onResultsName = modal.dataset.onResults;
            if (onResultsName) {
                var ct = response.headers.get('content-type') || '';
                if (!response.ok || !ct.includes('application/json')) {
                    throw new Error('Image search failed. Please try again.');
                }
                var json = await response.json();
                closeModal(modal);
                var handler = window[onResultsName];
                if (typeof handler === 'function') handler(json);
            } else if (response.redirected) {
                window.location.href = response.url;
            } else if (!response.ok) {
                throw new Error('Image search failed. Please try again.');
            }
        } catch (e) {
            modal.querySelector('.image-search-loading').classList.add('hidden');
            showError(modal, e.message || 'Image search failed. Please try again.');
            modal.querySelector('.image-search-preview').classList.remove('hidden');
        }
    }

    function initModal(modal) {
        if (modal.__ismInit) return;
        modal.__ismInit = true;

        modal.querySelector('.image-search-close').addEventListener('click', function () { closeModal(modal); });
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(modal); });

        modal.querySelector('.image-search-take-photo').addEventListener('click', function () { startCamera(modal); });
        modal.querySelector('.image-search-cancel-camera').addEventListener('click', function () {
            stopCamera(modal);
            modal.querySelector('.image-search-camera').classList.add('hidden');
            modal.querySelector('.image-search-choice').classList.remove('hidden');
        });
        modal.querySelector('.image-search-capture').addEventListener('click', function () { capturePhoto(modal); });

        var fileInput = modal.querySelector('.image-search-file-input');
        modal.querySelector('.image-search-upload').addEventListener('click', function () { fileInput.click(); });
        fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files[0]) showPreview(modal, fileInput.files[0]);
        });

        modal.querySelector('.image-search-retake').addEventListener('click', function () { resetModal(modal); });
        modal.querySelector('.image-search-submit').addEventListener('click', function () { submitSearch(modal); });
    }

    function boot() {
        document.querySelectorAll('.image-search-modal').forEach(initModal);
        document.querySelectorAll('[data-image-search-trigger]').forEach(function (btn) {
            if (btn.__ismTrigger) return;
            btn.__ismTrigger = true;
            btn.addEventListener('click', function () {
                var modal = document.getElementById(btn.dataset.imageSearchTrigger);
                if (modal) openModal(modal);
            });
        });
    }

    // Escape key closes any open modal
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.image-search-modal.flex').forEach(function (m) { closeModal(m); });
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    document.addEventListener('livewire:navigated', boot);
})();
