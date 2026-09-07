<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\Tenant\TenantCatalogSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResyncTenantChangeRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 0;

    public function __construct(
        public string $tenantId,
        public array $sections,
    ) {
        $this->onQueue('tenant-catalog-sync');
    }

    public function handle(TenantCatalogSyncService $service): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        if (!$tenant) {
            return;
        }

        $service->syncForTenant($tenant, $this->sections);
    }
}
