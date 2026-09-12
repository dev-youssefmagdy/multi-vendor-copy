import '@tenant-css/pages/blade-theme.css';
import { upload } from '@tenant/core/http.js';
import { toast } from '@tenant/core/toast.js';

const form = document.getElementById('bt-upload-form');

if (form) {
    const fileInput = form.querySelector('input[name="theme_zip"]');
    const submitBtn = form.querySelector('[data-bt-submit]');
    const progressWrap = document.getElementById('bt-upload-progress');
    const progressBar = progressWrap?.querySelector('.t-progress-bar');
    const progressValue = progressWrap?.querySelector('.t-progress-value');

    // Overrides the declarative x-tenant::form submit so we can track upload
    // progress on the ZIP; validation errors still render the same way.
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        event.stopImmediatePropagation();

        if (!fileInput?.files?.length) {
            toast.error('Please choose a theme ZIP to upload.');
            return;
        }

        const formData = new FormData();
        formData.append('theme_zip', fileInput.files[0]);

        submitBtn.disabled = true;
        if (progressWrap) {
            progressWrap.hidden = false;
            if (progressBar) progressBar.style.setProperty('--p', '0%');
            if (progressValue) progressValue.textContent = '0%';
        }

        try {
            await upload('post', form.action, formData, (percent) => {
                if (progressBar) progressBar.style.setProperty('--p', `${percent}%`);
                if (progressValue) progressValue.textContent = `${percent}%`;
            });

            form.reset();
            location.reload();
        } catch {
            // Toast already shown by the http client's response interceptor.
        } finally {
            submitBtn.disabled = false;
        }
    });
}
