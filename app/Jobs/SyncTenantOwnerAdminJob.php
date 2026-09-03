<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncTenantOwnerAdminJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $tenantId,
        public array $attributes,
        public ?string $previousEmail = null,
    ) {
        $this->onQueue('tenant-sync');
    }

    public function handle(TenantService $tenantService): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        if (!$tenant) {
            return;
        }

        $tenantService->syncTenantOwnerAdmin($tenant, $this->attributes, $this->previousEmail);
    }
}
