import '@tenant-css/pages/requests.css';
import { on } from '../../core/events.js';
import { getEcho } from '../../core/echo.js';

function readRequestId() {
    const el = document.getElementById('product-request-data');
    if (!el) {
        return null;
    }
    try {
        return JSON.parse(el.textContent).requestId;
    } catch {
        return null;
    }
}

const requestId = readRequestId();
const chatEl = document.getElementById('product-request-chat');

// The composer is an x-tenant::form with success="none": on a successful
// reply the server returns the rendered message in `response.data.message`,
// so the sender sees it instantly without waiting for the Echo broadcast.
on('tenant:form:success', ({ form, response }) => {
    if (!form || form.id !== 'product-request-reply-form') {
        return;
    }

    const message = response?.data?.message;
    if (message && chatEl?._tenantAppendMessage) {
        chatEl._tenantAppendMessage(message);
    }

    form.reset();
});

// Realtime: admin replies broadcast on ProductRequestMessageSent, on the
// tenant's product-requests channel (message.sent), same as the create/reply
// flow that fires it.
if (requestId && chatEl) {
    getEcho().then((echo) => {
        const tenantId = document.querySelector('meta[name="tenant-id"]')?.content;
        if (!tenantId) {
            return;
        }

        echo.private(`tenant.${tenantId}.product-requests`).listen('.message.sent', (e) => {
            if (e.request_id !== requestId || e.sender_type !== 'admin') {
                return;
            }

            chatEl._tenantAppendMessage?.({
                id: `${e.request_id}-${e.sent_at}`,
                author: e.sender_name,
                at: e.sent_at,
                body: e.body,
                is_me: false,
            });
        });
    });
}
