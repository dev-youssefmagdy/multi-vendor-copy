// Brand Request detail page: chat send/append, realtime admin messages via
// Echo, and the shared payment-gateway modal wired per payment-request row.
import { getEcho } from '../../core/echo.js';
import { openModal } from '../../core/modals.js';
import { on } from '../../core/events.js';

const context = JSON.parse(document.getElementById('brand-request-context')?.textContent || '{}');
const chatRoot = document.getElementById('brand-request-chat');
const chatForm = document.getElementById('brand-chat-form');

// Append the tenant's own message instantly from the send response, then
// reset the composer (the form uses success="none" so nothing else happens).
on('tenant:form:success', ({ form, response }) => {
    if (form !== chatForm) {
        return;
    }

    const message = response?.data?.message;
    if (message && chatRoot?._tenantAppendMessage) {
        chatRoot._tenantAppendMessage(message);
    }

    form.reset();
});

// Realtime: append admin messages as they arrive. The tenant's own messages
// are already appended above from the send response, so only admin-sent
// messages are appended here to avoid double-rendering.
if (context.tenantId && context.requestId) {
    getEcho().then((echo) => {
        echo.private(`tenant.${context.tenantId}.brand-requests.${context.requestId}`)
            .listen('.message.sent', (e) => {
                if (e.sender_type !== 'admin') {
                    return;
                }

                chatRoot?._tenantAppendMessage?.({
                    id: `admin-${e.sent_at}`,
                    author: e.sender_name,
                    at: e.sent_at,
                    body: e.body,
                    is_me: false,
                });
            });
    });
}

// Payment: each "Pay Now" row button fills the shared modal's hidden
// payment_request_id field and summary text, then opens it.
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-brand-pay-request]');
    if (!button) {
        return;
    }

    const modal = document.getElementById('brand-pay-modal');
    if (!modal) {
        return;
    }

    const labelEl = modal.querySelector('[data-brand-pay-summary-label]');
    const amountEl = modal.querySelector('[data-brand-pay-summary-amount]');

    if (labelEl) {
        labelEl.textContent = button.dataset.paymentRequestLabel ?? '';
    }
    if (amountEl) {
        amountEl.textContent = `${button.dataset.paymentRequestCurrency ?? ''} ${button.dataset.paymentRequestAmount ?? '0.00'}`;
    }

    openModal('brand-pay-modal', { fill: { payment_request_id: button.dataset.paymentRequestId } });
});
