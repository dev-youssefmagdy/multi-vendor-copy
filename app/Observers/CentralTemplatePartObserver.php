<?php

namespace App\Observers;

use App\Models\TemplatePart;
use App\Services\Tenant\CentralCatalogTenantSyncService;

class CentralTemplatePartObserver
{
    public function saved(TemplatePart $part): void
    {
        $part->refresh();
        // Parts live under the `themes` section — tenant sync picks them up
        // via the extended `syncThemes()` pipeline.
        app(CentralCatalogTenantSyncService::class)->syncAllTenants(['themes']);
    }

    public function deleted(TemplatePart $part): void
    {
        app(CentralCatalogTenantSyncService::class)->syncAllTenants(['themes']);
    }
}
