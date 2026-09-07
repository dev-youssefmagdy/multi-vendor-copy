@php
    $unread = \App\Models\AdminNotification::unread()->count();
@endphp
<a href="{{ route('admin.notifications.index') }}"
   id="admin-notif-bell"
   style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;flex-shrink:0"
   title="Notifications"
>
    <svg class="icon-t2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
    </svg>
    <span
        id="admin-notif-badge"
        class="badge badge-amber"
        style="position:absolute;top:0;right:0;min-width:16px;height:16px;font-size:9px;line-height:1;padding:0 4px;display:{{ $unread > 0 ? 'flex' : 'none' }};align-items:center;justify-content:center;transition:transform .2s"
    >{{ $unread > 0 ? $unread : '' }}</span>
</a>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.Echo) return;
        window.Echo.private('admin.notifications')
            .listen('.notification.created', () => {
                const badge = document.getElementById('admin-notif-badge');
                if (!badge) return;
                const current = parseInt(badge.textContent || '0', 10) || 0;
                badge.textContent = current + 1;
                badge.style.display = 'flex';
                badge.style.transform = 'scale(1.4)';
                setTimeout(() => badge.style.transform = 'scale(1)', 300);
            });
    });
</script>
