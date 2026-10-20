@php
    $unread = \App\Models\AdminNotification::unread()->count();
@endphp
<div id="admin-notif-bell"
     data-notif-bell
     data-latest-url="{{ route('admin.notifications.latest') }}"
     data-read-all-url="{{ route('admin.notifications.read-all') }}"
     data-read-url="{{ route('admin.notifications.read', '__ID__') }}"
     data-read-method="POST"
     data-all-url="{{ route('admin.notifications.index') }}"
     data-channel="admin.notifications"
     data-label-title="{{ __('Notifications') }}"
     data-label-read-all="{{ __('Mark all as read') }}"
     data-label-view-all="{{ __('View all notifications') }}"
     data-label-mark-read="{{ __('Mark as read') }}"
     data-label-empty="{{ __('No notifications yet') }}"
     data-label-sound="{{ __('Alert sound') }}"
     style="position:relative;display:inline-flex;flex-shrink:0;--nb-bg:var(--card);--nb-fg:var(--t1);--nb-muted:var(--t2);">
    <button type="button" data-notif-toggle title="{{ __('Notifications') }}" aria-label="{{ __('Notifications') }}"
            style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:none;border:0;cursor:pointer;color:inherit">
        <svg class="icon-t2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        <span data-notif-count data-display="flex"
              class="badge badge-amber"
              style="position:absolute;top:0;right:0;min-width:16px;height:16px;font-size:9px;line-height:1;padding:0 4px;display:{{ $unread > 0 ? 'flex' : 'none' }};align-items:center;justify-content:center"
              @unless($unread > 0) hidden @endunless>{{ $unread > 99 ? '99+' : $unread }}</span>
    </button>
</div>
