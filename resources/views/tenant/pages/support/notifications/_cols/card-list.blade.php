@forelse ($notifications as $notification)
    <div class="details-kv notification-card {{ !$notification->is_read ? 'notification-unread' : '' }}"
        data-notification-card="{{ $notification->id }}">
        <div class="notification-card-body">
            <div class="notification-card-head">
                <div class="entity-title">{{ $notification->title }}</div>
                <span class="badge badge-amber notification-new-badge" data-notification-new>New</span>
                <span class="entity-subtitle notification-card-time">{{ $notification->created_at->diffForHumans() }}</span>
            </div>
            <p class="panel-copy notification-card-message">{{ $notification->message }}</p>
            @if ($notification->data && count($notification->data) > 0)
                <div class="notification-card-meta">
                    @foreach ($notification->data as $key => $value)
                        @if ($value)
                            <span class="entity-subtitle">{{ str_replace('_', ' ', $key) }}: {{ $value }}</span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
        <button type="button" data-mark-read="{{ route('tenant.notifications.mark-read', $notification->id) }}" class="btn btn-secondary btn-sm notification-mark-read-btn">
            Mark Read
        </button>
    </div>
@empty
    <div class="empty-state">
        <div class="empty-state-title">No notifications yet</div>
        <p class="empty-state-copy">You'll receive notifications here when the admin updates your requests or adds
            products for you.</p>
    </div>
@endforelse
