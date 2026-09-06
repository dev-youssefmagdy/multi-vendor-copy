<?php

namespace App\Services;

use App\Events\TenantNotificationCreated;
use App\Models\Tenant;
use App\Models\Tenant\TenantNotification;

class TenantNotificationService
{
    public function notify(Tenant $tenant, string $type, string $title, string $message, array $data = []): void
    {
        tenancy()->initialize($tenant);

        try {
            $record = TenantNotification::create([
                'type'    => $type,
                'title'   => $title,
                'message' => $message,
                'data'    => $data ?: null,
                'is_read' => false,
            ]);

            try {
                event(new TenantNotificationCreated(
                    tenantId:       $tenant->getTenantKey(),
                    type:           $type,
                    title:          $title,
                    message:        $message,
                    notificationId: $record->id,
                ));
            } catch (\Throwable) {
                // Broadcast failure must never break the notification write
            }
        } finally {
            // Do not call tenancy()->end() as it may break the calling context
        }
    }

    public function notifyById(string $tenantId, string $type, string $title, string $message, array $data = []): void
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return;
        }

        $this->notify($tenant, $type, $title, $message, $data);
    }
}
