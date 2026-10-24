<?php

namespace App\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Last link of the post-registration catalog sync chain. */
class MarkTenantSyncReadyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $tenantId)
    {
        $this->onQueue('tenant-sync');
    }

    public function handle(): void
    {
        Tenant::saveData($this->tenantId, ['sync_status' => 'ready']);
    }
}
