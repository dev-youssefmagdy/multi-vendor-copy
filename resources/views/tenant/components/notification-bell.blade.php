@props(['unread' => 0])

<div class="t-notif-bell" data-tenant-notification-bell data-tenant-id="{{ tenant('id') }}">
    <a href="{{ route('tenant.notifications.index') }}" class="nav-icon-btn" title="Notifications" style="position:relative">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        <span class="notif-badge" data-notif-count @unless($unread > 0) hidden @endunless>{{ $unread > 99 ? '99+' : $unread }}</span>
    </a>
</div>
