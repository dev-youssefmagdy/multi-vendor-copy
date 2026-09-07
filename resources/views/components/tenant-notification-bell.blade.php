@php
    $unread = \App\Models\Tenant\TenantNotification::unread()->count();
    $tenantId = tenant()?->getTenantKey() ?? '';
@endphp
<div style="position:relative;display:inline-flex;align-items:center" x-data="tenantBell({{ $unread }}, '{{ $tenantId }}')">
    <a href="{{ route('tenant.notifications.index') }}"
       class="nav-icon-btn"
       title="Notifications"
       style="position:relative"
    >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        <span x-show="unread > 0" x-text="unread > 99 ? '99+' : unread" class="notif-badge"></span>
    </a>

    {{-- Toast container --}}
    <div
        class="notif-toast"
        x-show="toast.show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="toast.show = false"
    >
        <div class="notif-toast-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
        </div>
        <div>
            <div class="notif-toast-title" x-text="toast.title"></div>
            <div class="notif-toast-message" x-text="toast.message"></div>
        </div>
    </div>
</div>

<script>
    function tenantBell(initialUnread, tenantId) {
        return {
            unread: initialUnread,
            toast: { show: false, title: '', message: '' },
            init() {
                if (!window.Echo || !tenantId) return;
                window.Echo.private(`tenant.${tenantId}.notifications`)
                    .listen('.notification.created', (e) => {
                        this.unread += 1;
                        this.toast.title = e.title;
                        this.toast.message = e.message;
                        this.toast.show = true;
                        setTimeout(() => this.toast.show = false, 5000);
                    });

                window.addEventListener('notification-read', () => {
                    if (this.unread > 0) this.unread -= 1;
                });
            }
        };
    }
</script>
