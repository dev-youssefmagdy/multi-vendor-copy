<?php

namespace App\Observers;

use App\Models\CentralCoupon;
use App\Services\Tenant\CentralCatalogTenantSyncService;

class CentralCouponObserver
{
    public function saved(CentralCoupon $coupon): void
    {
        app(CentralCatalogTenantSyncService::class)->syncAllTenants(['coupons']);
    }

    public function deleted(CentralCoupon $coupon): void
    {
        app(CentralCatalogTenantSyncService::class)->syncAllTenants(['coupons']);
    }
}
