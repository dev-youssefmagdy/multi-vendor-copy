<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Tenant\TenantNotification;

class TenantNotificationService
{
    public function notify(Tenant $tenant, string $type, string $title, string $message, array $data = []): void
    {
        tenancy()->initialize($tenant);

        try {
            TenantNotification::create([
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data ?: null,
                'is_read' => false,
            ]);
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
