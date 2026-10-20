<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/** JSON endpoints behind the admin bell dropdown (see resources/js/notifications/bell.js). */
class AdminNotificationController extends Controller
{
    public function latest(): JsonResponse
    {
        $items = AdminNotification::query()->latest()->limit(10)->get()
            ->map(fn (AdminNotification $n) => $n->toFeedItem(route('admin.notifications.open', $n->id)))
            ->all();

        return response()->json([
            'unread' => AdminNotification::unread()->count(),
            'items' => $items,
        ]);
    }

    public function open(int $id): RedirectResponse
    {
        $notification = AdminNotification::findOrFail($id);
        $notification->markAsRead();

        return redirect()->to($notification->targetUrl() ?? route('admin.notifications.index'));
    }

    public function read(int $id): JsonResponse
    {
        AdminNotification::find($id)?->markAsRead();

        return response()->json(['unread' => AdminNotification::unread()->count()]);
    }

    public function readAll(): JsonResponse
    {
        AdminNotification::unread()->update(['is_read' => true]);

        return response()->json(['unread' => 0]);
    }
}
