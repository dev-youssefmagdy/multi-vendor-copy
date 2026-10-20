@props(['unread' => 0])

<div class="t-notif-bell"
     data-tenant-notification-bell
     data-notif-bell
     data-tenant-id="{{ tenant('id') }}"
     data-latest-url="{{ route('tenant.notifications.latest') }}"
     data-read-all-url="{{ route('tenant.notifications.read-all') }}"
     data-read-url="{{ route('tenant.notifications.mark-read', '__ID__') }}"
     data-read-method="PATCH"
     data-all-url="{{ route('tenant.notifications.index') }}"
     data-channel="tenant.{{ tenant('id') }}.notifications"
     data-label-title="{{ __('Notifications') }}"
     data-label-read-all="{{ __('Mark all as read') }}"
     data-label-view-all="{{ __('View all notifications') }}"
     data-label-mark-read="{{ __('Mark as read') }}"
     data-label-empty="{{ __('No notifications yet') }}"
     data-label-sound="{{ __('Alert sound') }}">
    <button type="button" data-notif-toggle class="nav-icon-btn hd-icon-btn hd-bell" title="{{ __('Notifications') }}" aria-label="{{ __('Notifications') }}" style="position:relative">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5.16 15.15c-.35.8.03 1.6.72 1.95 1.62.8 3.82 1.2 6.12 1.2s4.5-.4 6.12-1.2c.69-.35 1.07-1.15.72-1.95-.4-.93-1.34-1.65-1.34-3.6V9.5a5.5 5.5 0 1 0-11 0v2.05c0 1.95-.94 2.67-1.34 3.6z"/>
            <path d="M9.5 20.5c.5.9 1.4 1.5 2.5 1.5s2-.6 2.5-1.5"/>
        </svg>
        <span class="notif-badge" data-notif-count @unless($unread > 0) hidden @endunless>{{ $unread > 99 ? '99+' : $unread }}</span>
    </button>
</div>
