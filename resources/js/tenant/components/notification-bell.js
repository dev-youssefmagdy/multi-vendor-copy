import { getEcho } from '../core/echo.js';
import { toast } from '../core/toast.js';
import { on } from '../core/events.js';

export async function init(el) {
    const tenantId = el.dataset.tenantId;
    const countEl = el.querySelector('[data-notif-count]');
    let unread = parseInt(countEl?.textContent === '99+' ? '100' : countEl?.textContent || '0', 10) || 0;

    const render = () => {
        if (!countEl) {
            return;
        }
        countEl.hidden = unread <= 0;
        countEl.textContent = unread > 99 ? '99+' : String(unread);
    };

    if (tenantId) {
        try {
            const echo = await getEcho();
            echo.private(`tenant.${tenantId}.notifications`).listen('.notification.created', (e) => {
                unread += 1;
                render();
                toast.info(e.message, e.title);
            });
        } catch {
            /* realtime unavailable; badge still reflects the initial server count */
        }
    }

    on('tenant:notification-read', () => {
        if (unread > 0) {
            unread -= 1;
            render();
        }
    });

    on('tenant:notification-read-all', () => {
        unread = 0;
        render();
    });
}
