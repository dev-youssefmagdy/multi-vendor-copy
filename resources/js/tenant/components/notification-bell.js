import { getEcho } from '../core/echo.js';
import { toast } from '../core/toast.js';
import { on } from '../core/events.js';
import { mountNotificationBell } from '../../notifications/bell.js';

export function init(el) {
    mountNotificationBell(el, {
        getEcho,
        onToast: (e) => toast.info(e.message, e.title),
    });

    // The notifications page marks items read via its own buttons; keep the
    // dropdown badge honest by refetching the count when that happens.
    const refresh = () => el.dispatchEvent(new Event('notif:refresh'));
    on('tenant:notification-read', refresh);
    on('tenant:notification-read-all', refresh);
}
