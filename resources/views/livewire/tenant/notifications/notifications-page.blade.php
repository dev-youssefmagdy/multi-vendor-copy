<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">Notifications</h1>
                <span class="page-badge">Inbox</span>
                @if ($unreadCount > 0)
                    <span class="badge badge-amber">{{ $unreadCount }} unread</span>
                @endif
            </div>
            <p class="page-copy">View all notifications from the admin team including order updates and product
                assignments.</p>
        </div>
        @if ($unreadCount > 0)
            <div class="page-actions">
                <x-btn type="button" variant="secondary" wire:click="markAllRead">Mark All as Read</x-btn>
            </div>
        @endif
    </div>

    @if (session('status'))
        <div class="card section-gap notice-success">{{ session('status') }}</div>
    @endif

    <div class="card fu d2 table-card-shell section-gap">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">All Notifications</h3>
                <p class="panel-copy">{{ $notifications->total() }} total notifications.</p>
            </div>
        </div>

        @forelse ($notifications as $notification)
            <div class="details-kv {{ !$notification->is_read ? 'notification-unread' : '' }}"
                style="align-items:flex-start;gap:12px;padding:14px 20px;border-bottom:1px solid rgba(255,255,255,.06)"
                wire:key="notif-{{ $notification->id }}">
                <div style="flex:1;min-width:0">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <div class="entity-title">{{ $notification->title }}</div>
                        @if (!$notification->is_read)
                            <span class="badge badge-amber" style="font-size:10px">New</span>
                        @endif
                        <span class="entity-subtitle"
                            style="margin-left:auto">{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="panel-copy" style="margin-top:4px">{{ $notification->message }}</p>
                    @if ($notification->data && count($notification->data) > 0)
                        <div style="margin-top:6px">
                            @foreach ($notification->data as $key => $value)
                                @if ($value)
                                    <span class="entity-subtitle">{{ str_replace('_', ' ', $key) }}: {{ $value }}</span> &nbsp;
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
                @if (!$notification->is_read)
                    <button type="button" wire:click="markRead({{ $notification->id }})" class="btn btn-secondary btn-sm"
                        style="flex-shrink:0;margin-top:2px">
                        Mark Read
                    </button>
                @endif
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-state-title">No notifications yet</div>
                <p class="empty-state-copy">You'll receive notifications here when the admin updates your requests or adds
                    products for you.</p>
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
</main>