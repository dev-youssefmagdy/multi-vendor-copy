<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\Tenant\TenantCatalogSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncTenantSectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 0;

    public function __construct(
        public string $tenantId,
        public string $section,
    ) {
        $this->onQueue('tenant-sync');
    }

    public function handle(TenantCatalogSyncService $syncService): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        if (!$tenant) {
            return;
        }

        $syncService->syncSection($tenant, $this->section);

        if ($this->section === 'products') {
            ApplyTenantProfitPercentageJob::dispatch($tenant->id);
        }
    }
}
