<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Support;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Tenant\TenantNotification;
use App\Repositories\Tenant\TenantPanelRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationsController extends PanelController
{
    public function __construct(protected TenantPanelRepository $repo) {}

    public function index(): \Illuminate\View\View
    {
        $notifications = $this->repo->paginateNotifications();

        return view('tenant.pages.support.notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $this->repo->unreadNotificationsCount(),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $notifications = $this->repo->paginateNotifications();

        return $this->fragment(
            'tenant.pages.support.notifications._cols.card-list',
            ['notifications' => $notifications],
            $notifications
        );
    }

    public function latest(): JsonResponse
    {
        $items = TenantNotification::query()->latest()->limit(10)->get()
            ->map(fn (TenantNotification $n) => $n->toFeedItem(route('tenant.notifications.open', $n->id)))
            ->all();

        return response()->json([
            'unread' => TenantNotification::unread()->count(),
            'items' => $items,
        ]);
    }

    public function open(int $id): RedirectResponse
    {
        $notification = TenantNotification::findOrFail($id);
        $notification->markAsRead();

        return redirect()->to($notification->targetUrl() ?? route('tenant.notifications.index'));
    }

    public function markAllRead(): JsonResponse
    {
        TenantNotification::unread()->update(['is_read' => true]);

        return $this->success('All notifications marked as read.');
    }

    public function markRead(int $id): JsonResponse
    {
        TenantNotification::find($id)?->markAsRead();

        return $this->success('Notification marked as read.');
    }
}
