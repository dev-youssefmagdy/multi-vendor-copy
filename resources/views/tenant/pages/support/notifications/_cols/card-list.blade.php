@forelse ($notifications as $notification)
    <div class="notification-card {{ !$notification->is_read ? 'notification-unread' : '' }}"
        data-notification-card="{{ $notification->id }}">
        <div class="notification-card-body">
            <div class="notification-card-head">
                <h3 class="notification-card-title">{{ $notification->title }}</h3>
                <span class="notification-new-badge" data-notification-new>new</span>
            </div>
            <p class="notification-card-message">{{ $notification->message }}</p>
            @if ($notification->data && count($notification->data) > 0)
                <div class="notification-card-meta">
                    @foreach ($notification->data as $key => $value)
                        @if ($value)
                            <span>{{ str_replace('_', ' ', $key) }}: {{ $value }}</span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <div class="notification-card-side">
            <time class="notification-card-time" datetime="{{ $notification->created_at->toIso8601String() }}" title="{{ $notification->created_at->format('M d, Y H:i') }}">{{ $notification->created_at->diffForHumans() }}</time>
            <button type="button" data-mark-read="{{ route('tenant.notifications.mark-read', $notification->id) }}" class="notification-mark-read-btn">
                Mark as read
            </button>
        </div>
    </div>
@empty
    <div class="empty-state">
        <div class="empty-state-title">No notifications yet</div>
        <p class="empty-state-copy">You'll receive notifications here when the admin updates your requests or adds
            products for you.</p>
    </div>
@endforelse
