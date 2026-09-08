<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\Tenant\TenantCatalogSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncTenantSectionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 0;
    public int $timeout = 0;

    public function __construct(
        public string $tenantId,
        public ?array $sections = null,
    ) {
        $this->onQueue('tenant-sync');
    }

    public function handle(TenantCatalogSyncService $syncService): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        if (!$tenant) {
            return;
        }

        $sections = $syncService->normalizeSections($this->sections);

        foreach ($sections as $section) {
            SyncTenantSectionJob::dispatch($this->tenantId, $section);
        }
    }
}
