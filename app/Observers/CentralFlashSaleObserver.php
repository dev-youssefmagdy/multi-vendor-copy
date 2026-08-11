<?php

namespace App\Observers;

use App\Models\CentralFlashSale;
use App\Services\Tenant\CentralCatalogTenantSyncService;

class CentralFlashSaleObserver
{
    public function saved(CentralFlashSale $flashSale): void
    {
        app(CentralCatalogTenantSyncService::class)->syncAllTenants(['flash-sales']);
    }

    public function deleted(CentralFlashSale $flashSale): void
    {
        app(CentralCatalogTenantSyncService::class)->syncAllTenants(['flash-sales']);
    }
}
