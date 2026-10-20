# Notifications (admin + vendor panels)

- Write: `AdminNotificationService::notify()` / `TenantNotificationService::notify()` store the row and broadcast
  `notification.created` on `admin.notifications` / `tenant.{id}.notifications` (Reverb, `ShouldBroadcastNow`).
- Links: never stored. `App\Support\NotificationLinks` resolves the target page from `type` + `data` ids in the
  *viewer's* context (or an explicit `data.route` + `data.route_params`). Add new types/ids there.
- Bell UI (both panels): `resources/js/notifications/bell.js` (dropdown, mark one/all read, open → marks read and redirects)
  and `alert-sound.js` (WebAudio chime, mute toggle in localStorage, unlocked on first user gesture).
  Admin markup: `components/admin-notification-bell.blade.php`; vendor: `tenant/components/notification-bell.blade.php`.
- Endpoints: `admin.notifications.{latest,open,read,read-all}`, `tenant.notifications.{latest,open,mark-read,read-all}`.
- Runtime: `php artisan reverb:start` must be running. In production proxy `/app` and `/apps` (WebSocket) to Reverb
  on the same origin (`reverbConfig.js` connects via the page origin on non-local hosts).
