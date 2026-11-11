// Mail configurations — the settings form and the "send test email" modal are
// declarative x-tenant:: forms. Saving and testing verify the store's mail server
// and return the refreshed status banner, swapped in here (success or failure).
import { on, EVENTS } from '../../core/events.js';

function swapStatus(payload) {
    const html = payload?.mail_status_html;
    const current = document.querySelector('[data-mail-status]');

    if (!html || !current) {
        return;
    }

    const template = document.createElement('template');
    template.innerHTML = html.trim();
    const next = template.content.firstElementChild;

    if (next) {
        current.replaceWith(next);
    }
}

const isMailForm = (form) => ['mail-form', 'test-email-form'].includes(form?.id);

on(EVENTS.FORM_SUCCESS, ({ form, response }) => {
    if (isMailForm(form)) {
        swapStatus(response?.data);
    }
});

on(EVENTS.FORM_ERROR, ({ form, error }) => {
    if (isMailForm(form)) {
        swapStatus(error?.data?.data);
    }
});
