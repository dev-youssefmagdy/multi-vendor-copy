<?php

namespace App\Livewire\Tenant\Notifications;

use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\TenantNotification;
use Livewire\WithPagination;

class NotificationsPage extends TenantPage
{
    use InteractsWithTenantUi;
    use WithPagination;

    protected function pageMeta(): array
    {
        return [
            'title' => 'Notifications',
            'badge' => 'Inbox',
            'description' => 'View all notifications from the admin team including order updates and product assignments.',
        ];
    }

    protected function pageData(): array
    {
        $notifications = TenantNotification::query()
            ->latest()
            ->paginate(20);

        $unreadCount = TenantNotification::unread()->count();

        return array_merge(parent::pageData(), [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.notifications.notifications-page';
    }

    public function markAllRead(): void
    {
        TenantNotification::unread()->update(['is_read' => true]);
        $this->toast('All notifications marked as read.');
    }

    public function markRead(int $id): void
    {
        TenantNotification::find($id)?->markAsRead();
    }
}
