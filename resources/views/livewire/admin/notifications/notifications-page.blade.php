<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">Notifications</h1>
                <span class="page-badge">Admin Inbox</span>
                @if ($unreadCount > 0)
                    <span class="badge badge-amber">{{ $unreadCount }} unread</span>
                @endif
            </div>
            <p class="page-copy">Platform-wide alerts: orders, payments, returns, tenants, and more.</p>
        </div>
        <div class="page-actions">
            <select wire:model.live="typeFilter" class="form-select" style="min-width:140px">
                <option value="">All Types</option>
                @foreach($typeOptions as $opt)
                    <option value="{{ $opt }}">{{ ucfirst($opt) }}</option>
                @endforeach
            </select>
            @if ($unreadCount > 0)
                <x-btn type="button" variant="secondary" wire:click="markAllRead">Mark All Read</x-btn>
            @endif
        </div>
    </div>

    <div class="card fu d2 table-card-shell section-gap">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">All Notifications</h3>
                <p class="panel-copy">{{ $notifications->total() }} total notifications.</p>
            </div>
        </div>

        @forelse ($notifications as $notification)
            <div
                class="details-kv {{ !$notification->is_read ? 'notification-unread' : '' }}"
                style="align-items:flex-start;gap:12px;padding:14px 20px;border-bottom:1px solid rgba(255,255,255,.06)"
                wire:key="admin-notif-{{ $notification->id }}"
            >
                <div style="flex:1;min-width:0">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <span class="badge {{ match($notification->type) {
                            'order'   => 'badge-cyan',
                            'payment' => 'badge-green',
                            'return'  => 'badge-amber',
                            'tenant'  => 'badge-violet',
                            default   => '',
                        } }}" style="font-size:10px;text-transform:uppercase">{{ $notification->type }}</span>
                        <div class="entity-title">{{ $notification->title }}</div>
                        @if (!$notification->is_read)
                            <span class="badge badge-amber" style="font-size:10px">New</span>
                        @endif
                        <span class="entity-subtitle" style="margin-left:auto">
                            {{ $notification->created_at->diffForHumans() }}
                        </span>
                    </div>
                    <p class="panel-copy" style="margin-top:4px">{{ $notification->message }}</p>
                    @if ($notification->data && count($notification->data) > 0)
                        <div style="margin-top:6px;display:flex;gap:12px;flex-wrap:wrap">
                            @foreach ($notification->data as $key => $value)
                                @if ($value)
                                    <span class="entity-subtitle">
                                        {{ str_replace('_', ' ', $key) }}: <strong>{{ $value }}</strong>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
                <div style="display:flex;gap:8px;flex-shrink:0;margin-top:2px">
                    @if (!$notification->is_read)
                        <button type="button" wire:click="markRead({{ $notification->id }})"
                            class="btn btn-secondary btn-sm">Mark Read</button>
                    @endif
                    <button type="button" wire:click="deleteNotification({{ $notification->id }})"
                        wire:confirm="Delete this notification?"
                        class="btn btn-danger btn-sm">Delete</button>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-state-title">No notifications yet</div>
                <p class="empty-state-copy">Platform notifications will appear here as orders, payments, and tenant events occur.</p>
            </div>
        @endforelse

        @if ($notifications->hasPages())
            <div class="table-footer-shell">
                <div class="pagination-shell">
                    <x-pagination :paginator="$notifications" />
                </div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.Echo) return;
            window.Echo.private('admin.notifications')
                .listen('.notification.created', () => {
                    Livewire.dispatch('$refresh');
                });
        });
    </script>
</main>
