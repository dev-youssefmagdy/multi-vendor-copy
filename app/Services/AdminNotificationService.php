<?php

namespace App\Services;

use App\Models\AdminNotification;

class AdminNotificationService
{
    public function notify(string $type, string $title, string $message, array $data = []): void
    {
        $create = fn() => AdminNotification::create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data ?: null,
            'is_read' => false,
        ]);

        if (tenant()) {
            tenancy()->central($create);
            return;
        }

        $create();
    }
}
