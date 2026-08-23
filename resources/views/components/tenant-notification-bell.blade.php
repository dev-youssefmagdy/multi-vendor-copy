@php
    $unread = \App\Models\Tenant\TenantNotification::unread()->count();
    $tenantId = tenant()?->getTenantKey() ?? '';
@endphp
<div style="position:relative;display:inline-flex;align-items:center" x-data="tenantBell({{ $unread }}, '{{ $tenantId }}')">
    <a href="{{ route('tenant.notifications.index') }}"
       class="nav-icon-btn"
       title="Notifications"
       style="position:relative;display:inline-flex;align-items:center"
    >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        <span
            x-show="unread > 0"
            x-text="unread"
            class="badge badge-amber"
            style="position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;font-size:10px;display:flex;align-items:center;justify-content:center;padding:0 4px"
        ></span>
    </a>

    {{-- Toast container --}}
    <div
        x-show="toast.show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="position:fixed;bottom:24px;right:24px;z-index:9999;background:var(--card-bg,#1e1e2e);border:1px solid var(--border-color,rgba(255,255,255,.12));border-radius:10px;padding:14px 18px;max-width:340px;box-shadow:0 8px 32px rgba(0,0,0,.4);cursor:pointer"
        @click="toast.show = false"
    >
        <div style="display:flex;align-items:center;gap:10px">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-amber,#f59e0b)" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <div>
                <div style="font-weight:600;font-size:13px" x-text="toast.title"></div>
                <div style="font-size:12px;opacity:.75;margin-top:2px" x-text="toast.message"></div>
            </div>
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
