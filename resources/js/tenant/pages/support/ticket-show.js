import '@tenant-css/pages/support.css';
import { get } from '../../core/http.js';
import { getEcho } from '../../core/echo.js';
import { bindForms } from '../../core/forms.js';
import { initComponents } from '../../core/registry.js';
import { on } from '../../core/events.js';

function readTicketId() {
    const el = document.getElementById('support-ticket-data');
    if (!el) {
        return null;
    }
    try {
        return JSON.parse(el.textContent).ticketId;
    } catch {
        return null;
    }
}

const ticketId = readTicketId();
const container = document.getElementById('support-thread-container');

async function refreshThread() {
    if (!ticketId || !container) {
        return;
    }

    // Preserve an in-progress draft reply across the refetch.
    const draft = container.querySelector('[name="reply"]')?.value ?? '';

    const url = container.dataset.threadUrl;
    if (!url) {
        return;
    }

    let response;
    try {
        response = await get(url, {}, { toast: false });
    } catch {
        return;
    }

    container.innerHTML = response.data?.html ?? response.html ?? '';
    bindForms(container);
    await initComponents(container);

    const textarea = container.querySelector('[name="reply"]');
    if (textarea && draft) {
        textarea.value = draft;
    }
}

// The composer is an x-tenant::form with success="none": on a successful
// reply the sender's own message needs to appear too, so the page refetches
// its thread — the same behaviour used for incoming admin replies below.
on('tenant:form:success', ({ form }) => {
    if (form?.id === 'support-reply-form') {
        refreshThread();
    }
});

// The ticket page refetches its thread (rather than appending a single
// message) on every admin reply, per SupportTicketMessageSent.
if (ticketId) {
    getEcho().then((echo) => {
        const tenantId = document.querySelector('meta[name="tenant-id"]')?.content;
        if (!tenantId) {
            return;
        }

        echo.private(`tenant.${tenantId}.support`).listen('.message.sent', (e) => {
            if (e.ticket_id === ticketId && e.sender_type === 'admin') {
                refreshThread();
            }
        });
    });
}
