<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product as CentralProduct;
use App\Models\Tenant;
use App\Services\Tenant\TenantCatalogSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Propagates a single central product's price/stock/weight/details to a single
 * tenant, without re-syncing the tenant's entire product catalog.
 *
 * Queue: tenant-sync (same queue used by the rest of the tenancy sync pipeline).
 */
class SyncCentralProductToTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $centralProductId,
        public readonly string $tenantId,
    ) {
        $this->onQueue('tenant-sync');
    }

    public function handle(TenantCatalogSyncService $syncService): void
    {
        $centralProduct = CentralProduct::withTrashed()->find($this->centralProductId);
        $tenant = Tenant::query()->find($this->tenantId);

        if (!$centralProduct || !$tenant) {
            return;
        }

        try {
            $syncService->syncProductToTenant($centralProduct, $tenant);
        } catch (\Throwable $e) {
            Log::error(
                "SyncCentralProductToTenantJob: Failed to sync central product #{$this->centralProductId} " .
                "to tenant [{$this->tenantId}]: " . $e->getMessage()
            );
        } finally {
            tenancy()->end();
        }
    }
}
