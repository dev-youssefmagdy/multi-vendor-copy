import '@tenant-css/pages/appearance.css';
import { post } from '@tenant/core/http.js';
import { confirm } from '@tenant/core/confirm.js';
import { toast } from '@tenant/core/toast.js';
import { setBusy } from '@tenant/core/dom.js';
import '@tenant/modules/logo-builder.js';

// Colors tab: "Reset to Default" refills the matching form's color inputs
// from the response instead of a full page reload, so the admin can review
// the defaults and still hit "Save Colors" explicitly.
document.querySelectorAll('[data-reset-colors-url]').forEach((btn) => {
    btn.addEventListener('click', async () => {
        const ok = await confirm({ text: "Reset these colors to the theme's default?" });
        if (!ok) {
            return;
        }

        const formId = btn.dataset.resetColorsForm;
        const form = formId ? document.getElementById(formId) : null;

        setBusy(btn, true);
        try {
            const response = await post(btn.dataset.resetColorsUrl, {}, { toast: true });
            if (form) {
                const { TenantForm } = await import('@tenant/core/forms.js');
                TenantForm.for(form).fill(response.data?.defaults ?? {}, 'values');
            }
        } catch {
            // Handled by the http client's error interceptor.
        } finally {
            setBusy(btn, false);
        }
    });
});
