import '@tenant-css/pages/notifications.css';

import { patch, post } from '../../core/http.js';
import { emit } from '../../core/events.js';

function init() {
    const list = document.getElementById('notifications-list');
    if (!list) {
        return;
    }

    const markAllBtn = document.querySelector('[data-mark-all-read]');
    const unreadBadge = document.querySelector('[data-notifications-unread-badge]');

    markAllBtn?.addEventListener('click', async () => {
        try {
            await post(markAllBtn.dataset.markAllRead);

            list.querySelectorAll('[data-notification-card]').forEach((card) => {
                card.classList.remove('notification-unread');
            });

            if (unreadBadge) {
                unreadBadge.hidden = true;
            }
            markAllBtn.hidden = true;

            emit('tenant:notification-read-all');
        } catch {
            /* handled by http interceptor */
        }
    });

    list.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-mark-read]');
        if (!btn) {
            return;
        }

        const card = btn.closest('[data-notification-card]');

        try {
            await patch(btn.dataset.markRead, null, { toast: false });

            card?.classList.remove('notification-unread');

            emit('tenant:notification-read');
        } catch {
            /* handled by http interceptor */
        }
    });
}

document.addEventListener('DOMContentLoaded', init);
