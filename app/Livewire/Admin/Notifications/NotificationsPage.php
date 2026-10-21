<?php

namespace App\Livewire\Admin\Notifications;

use App\Livewire\Admin\Base\AdminPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\AdminNotification;
use Livewire\WithPagination;

class NotificationsPage extends AdminPage
{
    use InteractsWithAdminUi;
    use WithPagination;

    public string $typeFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title'       => 'Notifications',
            'badge'       => 'Inbox',
            'description' => 'Platform-wide notifications: orders, payments, returns, tenant registrations, and more.',
        ];
    }

    protected function pageData(): array
    {
        $query = AdminNotification::query()->latest();

        if ($this->typeFilter !== '') {
            $query->where('type', $this->typeFilter);
        }

        $notifications = $query->paginate(25);
        $unreadCount   = AdminNotification::unread()->count();

        return array_merge(parent::pageData(), [
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
            'typeOptions'   => AdminNotification::query()->distinct()->orderBy('type')->pluck('type')->all(),
        ]);
    }

    protected function pageView(): string
    {
        return 'livewire.admin.notifications.notifications-page';
    }

    public function markRead(int $id): void
    {
        AdminNotification::find($id)?->update(['is_read' => true]);
        $this->dispatch('notifications-changed');
    }

    public function markAllRead(): void
    {
        AdminNotification::unread()->update(['is_read' => true]);
        $this->toast('All notifications marked as read.');
        $this->dispatch('notifications-changed');
    }

    public function deleteNotification(int $id): void
    {
        AdminNotification::find($id)?->delete();
        $this->toast('Notification deleted.');
        $this->dispatch('notifications-changed');
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }
}
