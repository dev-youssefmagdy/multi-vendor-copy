<?php

namespace App\Services;

use App\Events\AdminNotificationCreated;
use App\Models\AdminNotification;

class AdminNotificationService
{
    public function notify(string $type, string $title, string $message, array $data = []): void
    {
        $create = function () use ($type, $title, $message, $data): AdminNotification {
            return AdminNotification::create([
                'type'    => $type,
                'title'   => $title,
                'message' => $message,
                'data'    => $data ?: null,
                'is_read' => false,
            ]);
        };

        if (tenant()) {
            $record = tenancy()->central($create);
        } else {
            $record = $create();
        }

        if ($record instanceof AdminNotification) {
            try {
                event(new AdminNotificationCreated(
                    type:           $type,
                    title:          $title,
                    message:        $message,
                    notificationId: $record->id,
                ));
            } catch (\Throwable) {
                // Broadcast failure must never break notification write
            }
        }
    }
}
