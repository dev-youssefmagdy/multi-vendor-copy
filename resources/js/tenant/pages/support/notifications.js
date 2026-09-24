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

    // Keep the "N unread" pill (and the mark-all button) in step with the list.
    const setUnread = (count) => {
        if (!unreadBadge) return;
        const next = Math.max(0, count);
        unreadBadge.dataset.count = String(next);
        unreadBadge.textContent = `${next} unread`;
        unreadBadge.hidden = next === 0;
        if (markAllBtn) markAllBtn.hidden = next === 0;
    };

    markAllBtn?.addEventListener('click', async () => {
        try {
            await post(markAllBtn.dataset.markAllRead);

            list.querySelectorAll('[data-notification-card]').forEach((card) => {
                card.classList.remove('notification-unread');
            });

            setUnread(0);
            if (markAllBtn) markAllBtn.hidden = true;

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
        btn.classList.add('is-loading');

        try {
            await patch(btn.dataset.markRead, null, { toast: false });

            if (card?.classList.contains('notification-unread')) {
                card.classList.remove('notification-unread');
                setUnread(Number(unreadBadge?.dataset.count || 0) - 1);
            }

            emit('tenant:notification-read');
        } catch {
            /* handled by http interceptor */
        } finally {
            btn.classList.remove('is-loading');
        }
    });
}

document.addEventListener('DOMContentLoaded', init);
